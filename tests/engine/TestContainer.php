<?php

namespace Tests\Engine;

use Nette\Configurator;

trait TestContainer
{
    /** @var Nette\DI\Container */
    private $container;


    protected function getContainer()
    {
        if ($this->container === NULL) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    protected function createContainer()
    {
        $configurator = new Configurator();

        $configurator->setTempDirectory(dirname(TMP_DIR)); // shared container for performance purposes
        $configurator->setDebugMode(true);
        $configurator->addConfig(CONFIG_DIR . '/tests.neon');

        return $configurator->createContainer();
    }

}