<?php

namespace Tests\Mailino;

use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Config;


require_once __DIR__ . '/../bootstrap.php';

class ConfigTest extends ContainerTestCase
{
    public function testFrom()
    {
        $config = $this->getContainer()->getByType(Config::class);

        $from = $config->getValue('from');
        Assert::equal('sender@test.com', $from->email);
        Assert::equal('Sender', $from->name);
    }

    public function testReplyTo()
    {
        $config = $this->getContainer()->getByType(Config::class);

        $replyTo = $config->getValue('reply_to');
        Assert::equal('noreply@test.com', $replyTo->email);
        Assert::equal('No Reply', $replyTo->name);
    }

    public function testReturnPath()
    {
        $config = $this->getContainer()->getByType(Config::class);

        Assert::equal('bounce@test.com', $config->getValue('return_path'));
    }

    public function testPriority()
    {
        $config = $this->getContainer()->getByType(Config::class);

        Assert::equal(3, $config->getValue('priority'));
    }

    public function testHeaders()
    {
        $config = $this->getContainer()->getByType(Config::class);

        Assert::equal(['X-Custom' => 'test-value'], $config->getValue('headers'));
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
