<?php

namespace Tests\Mailino;

use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Config;


require_once __DIR__ . '/../bootstrap.php';

class ConfigTest extends ContainerTestCase
{
    public function testGetValue_Valid()
    {
        $config = $this->getContainer()->getByType(Config::class);

        Assert::equal((object)[ 'email' => 'sender@test.com', 'name' => 'Sender' ], $config->getValue('sender'));
    }

    public function testGetValue_Invalid()
    {
        $config = $this->getContainer()->getByType(Config::class);

        Assert::null($config->getValue('non-existent'));
    }

    public function testGetExtension_Valid()
    {
        $config = $this->getContainer()->getByType(Config::class);

        $extension = $config->getExtension('prefix');
        Assert::equal([ 'subject'   => 'TST' ], $extension);
    }

    public function testGetExtension_Invalid()
    {
        $config = $this->getContainer()->getByType(Config::class);

        Assert::throws(function() use ($config) {
            $config->getExtension('non-existent');
        }, InvalidArgumentException::class);
    }
}

(new ConfigTest())->run();
