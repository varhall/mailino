<?php

declare(strict_types=1);

namespace Varhall\Mailino;

use Nette\InvalidArgumentException;

/**
 * Validated Mailino configuration
 */
class Config
{
    protected \stdClass $config;

    public function __construct(\stdClass $config)
    {
        $this->config = $config;
    }

    public function getValue(string $option): mixed
    {
        return property_exists($this->config, $option) ? $this->config->$option : null;
    }

    public function hasExtension(string $identifier): bool
    {
        return isset($this->config->extensions[$identifier]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtension(string $identifier): array
    {
        if (!$this->hasExtension($identifier)) {
            throw new InvalidArgumentException("Extension {$identifier} is not configured, add section '{$identifier}' to mailino.extensions configuration");
        }

        return $this->config->extensions[$identifier];
    }
}
