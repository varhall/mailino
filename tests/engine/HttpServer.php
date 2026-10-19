<?php

namespace Tests\Engine;

/**
 * PHP built-in web server running on random port for integration tests
 */
class HttpServer
{
    /** @var resource */
    private $process;

    private int $port;

    public function __construct(string $router)
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->port = random_int(20000, 60000);
            $command = [ PHP_BINARY, '-S', "127.0.0.1:{$this->port}", $router ];
            $this->process = proc_open($command, [ 1 => [ 'file', '/dev/null', 'w' ], 2 => [ 'file', '/dev/null', 'w' ] ], $pipes);

            if ($this->waitForStart()) {
                return;
            }

            $this->stop();
        }

        throw new \RuntimeException('Unable to start PHP built-in server');
    }

    public function getUrl(): string
    {
        return "http://127.0.0.1:{$this->port}";
    }

    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
        }
    }

    private function waitForStart(): bool
    {
        for ($i = 0; $i < 100; $i++) {
            if (!proc_get_status($this->process)['running']) {
                return false;
            }

            $socket = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.1);

            if ($socket !== false) {
                fclose($socket);
                return true;
            }

            usleep(50000);
        }

        return false;
    }
}
