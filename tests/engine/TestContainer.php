<?php

namespace Tests\Engine;

use Nette\Bootstrap\Configurator;

trait TestContainer
{
    /** @var Nette\DI\Container */
    private $container;


    protected function getContainer()
    {
        if ($this->container === null) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    /**
     * @param array|string $config  additional configuration (array or path to neon file)
     * @param array $dynamicParameters  parameters known at runtime only (e.g. %env.*%)
     */
    protected function createContainer(array|string $config = [], array $dynamicParameters = [])
    {
        $configurator = new Configurator();

        $configurator->setTempDirectory(dirname(TMP_DIR)); // shared container for performance purposes
        $configurator->setDebugMode(true);
        $configurator->addStaticParameters([ 'fixturesDir' => FIXTURES_DIR ]);
        $configurator->addDynamicParameters($dynamicParameters);
        $configurator->addConfig(CONFIG_DIR . '/tests.neon');

        if (!empty($config)) {
            $configurator->addConfig($config);
        }

        return $configurator->createContainer();
    }
}
