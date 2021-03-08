<?php

namespace Varhall\Mailino\Tests\Unit\Services;

use Latte\Engine;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Qferrer\Mjml\Renderer\RendererInterface;
use Tester\Assert;
use Tests\Engine\BaseServiceTestCase;
use Varhall\Mailino\Entity\Mail;
use Varhall\Mailino\Mailino;
use Varhall\Mailino\Services\MjmlMailService;

require_once __DIR__ . '/../../../bootstrap.php';

class MjmlMailServiceTest extends BaseServiceTestCase
{
    public function testCreateMail()
    {
        $mailino = \Mockery::mock(Mailino::class);
        $mailino->shouldReceive('getConfig')->andReturn($this->getConfig());

        $service = new MjmlMailService($mailino);

        $mail = $service->createMail('html/test', []);

        Assert::type(Mail::class, $mail);
        Assert::equal('html/test', $mail->getTemplate());
        Assert::equal([ 'sender@test.com' => 'Sender' ], $mail->getFrom());
        Assert::equal('TST', $mail->getSubjectPrefix());
    }

    public function testSend()
    {
        $latte = \Mockery::mock(Engine::class);
        $latte->shouldReceive('renderToString')
            ->times(2)
            ->andReturnValues([ 'plain', '<mjml>html</mjml>' ]);

        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->andReturnUsing(function(Message $message) {
            Assert::equal('plain', $message->getBody());
            Assert::equal('<p>html</p>', $message->getHtmlBody());
        });

        $renderer = \Mockery::mock(RendererInterface::class);
        $renderer->shouldReceive('render')->andReturn('<p>html</p>');

        $mailino = new Mailino($this->getConfig(), $mailer, $latte, $renderer);

        $mail = new Mail('html/test', []);

        $service = new MjmlMailService($mailino);
        $service->send($mail);
    }
}

(new MjmlMailServiceTest())->run();