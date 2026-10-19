<?php

namespace Tests\Mailino;

use Nette\DI\InvalidConfigurationException;
use Nette\InvalidArgumentException;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Config;
use Varhall\Mailino\DI\MailinoExtension;

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

        Assert::throws(function () use ($config) {
            $config->getExtension('non-existent');
        }, InvalidArgumentException::class);
    }

    public function testGetExtension_MjmlApiDefaults()
    {
        $config = $this->getContainer()->getByType(Config::class);

        Assert::equal([
            'id'                => '123456',
            'secret'            => '654321',
            'url'               => 'https://mjml.local/v1',
            'timeout'           => 30,
            'connect_timeout'   => 10,
            'validation_level'  => 'soft',
        ], $config->getExtension('mjml.api'));
    }

    public function testGetExtension_NotConfigured()
    {
        $config = new Config($this->processSchema([ 'extensions' => [ 'mjml.binary' => [ 'binary' => 'mjml' ] ] ]));

        Assert::false($config->hasExtension('prefix'));
        Assert::false($config->hasExtension('mjml.api'));
        Assert::true($config->hasExtension('mjml.binary'));
        Assert::exception(fn() => $config->getExtension('prefix'), InvalidArgumentException::class, '~not configured~');
    }

    public function testGetExtension_Custom()
    {
        $config = $this->createContainer([ 'mailino' => [ 'extensions' => [ 'custom' => [ 'foo' => 'bar' ] ] ] ])->getByType(Config::class);

        Assert::equal([ 'foo' => 'bar' ], $config->getExtension('custom'));
    }

    public function getInvalidConfigs()
    {
        return [
            [ [ 'extensions' => [ 'mjml.api' => [ 'id' => 'x', 'secrect' => 'typo' ] ] ] ],
            [ [ 'extensions' => [ 'mjml.api' => [ 'id' => 'x', 'secret' => 'y', 'validation_level' => 'foo' ] ] ] ],
            [ [ 'extensions' => [ 'mjml.binary' => [] ] ] ],
            [ [ 'extensions' => [ 'prefix' => [ 'subject' => [ 'array' ] ] ] ] ],
            [ [ 'priority' => 9 ] ],
            [ [ 'headers' => [ 'X-Foo' => [ 'array' ] ] ] ],
        ];
    }

    /** @dataProvider getInvalidConfigs */
    public function testInvalidConfig(array $config)
    {
        Assert::exception(fn() => $this->processSchema($config), ValidationException::class);
    }

    public function testInvalidConfig_Container()
    {
        Assert::exception(fn() => $this->createContainer([ 'mailino' => [ 'priority' => 0 ] ]), InvalidConfigurationException::class);
    }

    private function processSchema(array $config): object
    {
        $base = [ 'template_dir' => FIXTURES_DIR, 'from' => [ 'email' => 'sender@test.com' ] ];

        return (new Processor())->process((new MailinoExtension())->getConfigSchema(), $base + $config);
    }
}

(new ConfigTest())->run();
