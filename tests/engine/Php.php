<?php

namespace Tests\Engine;

class Php
{
    /**
     * PHP CLI binary for child processes. PHP_BINARY is phpdbg when tests run under phpdbg
     * (code coverage), which can't run scripts or built-in server non-interactively.
     */
    public static function binary(): string
    {
        if (PHP_SAPI !== 'phpdbg' && !str_contains(basename(PHP_BINARY), 'phpdbg')) {
            return PHP_BINARY;
        }

        foreach ([ dirname(PHP_BINARY) . '/php', PHP_BINDIR . '/php' ] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }
}
