<?php

namespace Varhall\Mailino\Tests\Unit\Config;

use Tester\Assert;
use Tester\TestCase;
use Varhall\Mailino\Config\Sender;

require_once __DIR__ . '/../../../bootstrap.php';

class SenderTest extends TestCase
{
    public function testGetters()
    {
        $sender = new Sender('sender@test.com', 'Sender');

        Assert::equal('sender@test.com', $sender->getEmail());
        Assert::equal('Sender', $sender->getName());
    }
}

(new SenderTest())->run();
