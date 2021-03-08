<?php

namespace Tests\Engine;

use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tester\TestCase;
use Varhall\Mailino\DI\MailinoExtension;

abstract class BaseTestCase extends TestCase
{

    protected function createContainer($config = null): Container
    {
        $loader = new ContainerLoader(TEMP_DIR);

        $className = $loader->load(function (Compiler $compiler) use ($config) {
            $compiler->addExtension('mailino', new MailinoExtension());

            if (is_array($config)) {
                $compiler->addConfig($config);

            } else if (is_file($config)) {
                $compiler->loadConfig($config);

            } else {
                throw new \RuntimeException('Unsupported config');
            }
        }, md5(serialize([microtime(true), mt_rand(0, 1000), $config])));

        return new $className;
    }

}