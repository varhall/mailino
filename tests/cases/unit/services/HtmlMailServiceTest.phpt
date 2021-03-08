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
use Varhall\Mailino\Services\HtmlMailService;

require_once __DIR__ . '/../../../bootstrap.php';

class HtmlMailServiceTest extends BaseServiceTestCase
{
    public function testCreateMail()
    {
        $mailino = \Mockery::mock(Mailino::class);
        $mailino->shouldReceive('getConfig')->andReturn($this->getConfig());

        $service = new HtmlMailService($mailino);

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
            ->andReturnValues([ 'plain', '<p>html</p>' ]);

        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->andReturnUsing(function(Message $message) {
            Assert::equal('plain', $message->getBody());
            Assert::equal('<p>html</p>', $message->getHtmlBody());
        });

        $mailino = new Mailino($this->getConfig(), $mailer, $latte, \Mockery::mock(RendererInterface::class));

        $mail = new Mail('html/test', []);

        $service = new HtmlMailService($mailino);
        $service->send($mail);
    }
}

(new HtmlMailServiceTest())->run();