<?php

namespace Varhall\Mailino;

use Nette\InvalidArgumentException;

class Config
{
    protected object $config;

    public function __construct(object $config)
    {
        $this->config = $config;
    }

    public function getValue(string $option)
    {
        return property_exists($this->config, $option) ? $this->config->$option : null;
    }

    public function getExtension(string $identifier): array
    {
        if (!array_key_exists($identifier, $this->config->extensions))
            throw new InvalidArgumentException("Extension {$identifier} is not registered");

        return $this->config->extensions[$identifier];
    }
}