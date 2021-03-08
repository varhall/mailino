<?php

namespace Varhall\Mailino\Tests\Unit\Config;

use Tester\Assert;
use Tester\TestCase;
use Varhall\Mailino\Config\Config;
use Varhall\Mailino\Config\Sender;

require_once __DIR__ . '/../../../bootstrap.php';

class ConfigTest extends TestCase
{
    public function testGetters()
    {
        $sender = \Mockery::mock(Sender::class);

        $config = new Config('directory', $sender, 'TST', true);

        Assert::equal('directory', $config->getTemplateDir());
        Assert::equal('TST', $config->getSubjectPrefix());
        Assert::same($sender, $config->getSender());
        Assert::true($config->isVerifySSL());
    }
}

(new ConfigTest())->run();
