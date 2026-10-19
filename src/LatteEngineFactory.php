<?php

declare(strict_types=1);

namespace Varhall\Mailino;

class LatteEngineFactory implements ILatteEngineFactory
{
    private ?string $tempDir;

    /**
     * @param string|null $tempDir  directory for compiled templates cache
     */
    public function __construct(?string $tempDir = null)
    {
        $this->tempDir = $tempDir;
    }

    public function createLatte(): \Latte\Engine
    {
        return (new \Latte\Engine())->setTempDirectory($this->tempDir);
    }
}
