<?php

namespace Varhall\Mailino\Tests\Unit;

use Latte\Engine;
use Nette\Mail\Mailer;
use Qferrer\Mjml\Renderer\RendererInterface;
use Tester\Assert;
use Tester\TestCase;
use Varhall\Mailino\Config\Config;
use Varhall\Mailino\Mailino;


require_once __DIR__ . '/../../bootstrap.php';

class MailinoTest extends TestCase
{
    public function testGetters()
    {
        $config = \Mockery::mock(Config::class);
        $mailer = \Mockery::mock(Mailer::class);
        $latte = \Mockery::mock(Engine::class);
        $renderer = \Mockery::mock(RendererInterface::class);

        $mailino = new Mailino($config, $mailer, $latte, $renderer);

        Assert::same($config, $mailino->getConfig());
        Assert::same($mailer, $mailino->getMailer());
        Assert::same($renderer, $mailino->getRenderer());
        Assert::same($latte, $mailino->getLatte());
    }
}

(new MailinoTest())->run();
