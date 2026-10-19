<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions\Mjml;

use Varhall\Mailino\Exceptions\MjmlException;

/**
 * Renders MJML using local MJML CLI. Reads stdout and stderr concurrently (no deadlock on large output)
 * and kills the process after timeout.
 */
class MjmlBinaryRenderer implements IMjmlRenderer
{
    /** input from stdin, output to stdout, minified */
    public const ARGUMENTS = [ '-i', '-s', '--config.validationLevel', '--config.minify' ];

    /** @var string|list<string> */
    private string|array $binary;

    private int $timeout;

    /**
     * @param string|string[] $binary  executable with optional arguments; string is executed by shell
     *                                 (e.g. 'npx mjml'), array is executed directly without shell
     * @param int $timeout  seconds, 0 = no limit
     */
    public function __construct(string|array $binary, int $timeout = 30)
    {
        $this->binary = is_array($binary) ? array_values($binary) : $binary;
        $this->timeout = $timeout;
    }

    public function render(string $mjml): string
    {
        $command = is_array($this->binary)
            ? array_merge($this->binary, self::ARGUMENTS)
            : $this->binary . ' ' . implode(' ', array_map('escapeshellarg', self::ARGUMENTS));

        $process = proc_open($command, [ 0 => [ 'pipe', 'r' ], 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ], $pipes);

        if (!is_resource($process)) {
            throw new MjmlException('Unable to create MJML process.');
        }

        [ $stdout, $stderr, $timedOut ] = $this->communicate($process, $pipes, $mjml);

        if ($timedOut) {
            proc_terminate($process, 9);
            proc_close($process);
            throw new MjmlException("MJML process timed out after {$this->timeout} s.");
        }

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new MjmlException(trim($stderr) !== '' ? trim($stderr) : "MJML process failed with exit code {$exitCode}.");
        }

        return $stdout;
    }

    /**
     * @param resource $process
     * @param resource[] $pipes
     * @return array{0: string, 1: string, 2: bool}
     */
    private function communicate($process, array $pipes, string $input): array
    {
        $stdout = '';
        $stderr = '';
        $deadline = $this->timeout > 0 ? microtime(true) + $this->timeout : null;

        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $writers = $input === '' ? [] : [ $pipes[0] ];
        $readers = [ $pipes[1], $pipes[2] ];

        if ($input === '') {
            fclose($pipes[0]);
        }

        while ($readers || $writers) {
            if ($deadline !== null && microtime(true) > $deadline) {
                $this->closePipes(array_merge($readers, $writers));
                return [ $stdout, $stderr, true ];
            }

            $read = $readers;
            $write = $writers;
            $except = null;

            if (@stream_select($read, $write, $except, 0, 200000) === false) {
                break;
            }

            foreach ($write as $pipe) {
                // process may exit before reading whole input (error), its exit code is reported below
                $written = @fwrite($pipe, $input);
                $input = $written === false ? '' : substr($input, $written);

                if ($input === '') {
                    fclose($pipe);
                    $writers = [];
                }
            }

            foreach ($read as $pipe) {
                $chunk = fread($pipe, 65536);

                if ($pipe === $pipes[1]) {
                    $stdout .= (string) $chunk;
                } else {
                    $stderr .= (string) $chunk;
                }

                if (feof($pipe)) {
                    fclose($pipe);
                    $readers = array_values(array_filter($readers, fn($r) => $r !== $pipe));
                }
            }
        }

        return [ $stdout, $stderr, false ];
    }

    /**
     * @param resource[] $pipes
     */
    private function closePipes(array $pipes): void
    {
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }
}
