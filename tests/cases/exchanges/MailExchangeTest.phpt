<?php

namespace Tests\Mailino;

use Nette\InvalidStateException;
use Nette\Mail\Mailer;
use Tester\Assert;
use Tester\TestCaseException;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Exchanges\MailExchange;

require_once __DIR__ . '/../../bootstrap.php';

class MailExchangeTest extends BaseTestCase
{
    private function mailMock()
    {
        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getFrom')->andReturn(new Recipient('from@test.cz', 'From'));
        $mail->shouldReceive('getSubject')->andReturn('subject')->byDefault();
        $mail->shouldReceive('getPlainBody')->andReturn('plain')->byDefault();
        $mail->shouldReceive('getHtmlBody')->andReturn('html')->byDefault();
        $mail->shouldReceive('getTo')->andReturn([
            new Recipient('to1@test.cz', 'To1'),
            new Recipient('to2@test.cz', 'To2')
        ])->byDefault();
        $mail->shouldReceive('getCc')->andReturn([
            new Recipient('cc1@test.cz', 'Cc'),
            new Recipient('cc2@test.cz', 'Cc')
        ])->byDefault();
        $mail->shouldReceive('getBcc')->andReturn([
            new Recipient('bcc1@test.cz', 'Bcc1'),
            new Recipient('bcc2@test.cz', 'Bcc2')
        ])->byDefault();
        $mail->shouldReceive('getFiles')->andReturn([
            new Attachment('content 1', 'text/plain', 'attachment1.txt'),
            new Attachment('content 2', 'text/plain', 'attachment2.txt')
        ]);
        $mail->shouldReceive('getReplyTo')->andReturn([])->byDefault();
        $mail->shouldReceive('getReturnPath')->andReturn(null)->byDefault();
        $mail->shouldReceive('getPriority')->andReturn(null)->byDefault();
        $mail->shouldReceive('getHeaders')->andReturn([])->byDefault();
        $mail->shouldReceive('getBasePath')->andReturn(null)->byDefault();

        return $mail;
    }

    public function testSend()
    {
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send');

        $exchange = new MailExchange($mailer);
        $exchange->send($this->mailMock());
    }

    public function testSend_ReplyTo()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getReplyTo')->andReturn([
            new Recipient('reply@test.cz', 'Reply'),
        ]);

        $sentMessage = null;
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->with(\Mockery::capture($sentMessage));

        $exchange = new MailExchange($mailer);
        $exchange->send($mail);

        Assert::notNull($sentMessage);
        Assert::equal(['reply@test.cz' => 'Reply'], $sentMessage->getHeader('Reply-To'));
    }

    public function testSend_ReturnPath()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getReturnPath')->andReturn('bounce@test.cz');

        $sentMessage = null;
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->with(\Mockery::capture($sentMessage));

        $exchange = new MailExchange($mailer);
        $exchange->send($mail);

        Assert::equal('bounce@test.cz', $sentMessage->getReturnPath());
    }

    public function testSend_Priority()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getPriority')->andReturn(1);

        $sentMessage = null;
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->with(\Mockery::capture($sentMessage));

        $exchange = new MailExchange($mailer);
        $exchange->send($mail);

        Assert::equal(1, $sentMessage->getPriority());
    }

    public function testSend_Headers()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getHeaders')->andReturn(['X-Custom' => 'my-value']);

        $sentMessage = null;
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->with(\Mockery::capture($sentMessage));

        $exchange = new MailExchange($mailer);
        $exchange->send($mail);

        Assert::equal('my-value', $sentMessage->getHeader('X-Custom'));
    }

    public function testEvents_Success()
    {
        $mail = $this->mailMock();
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send');

        $exchange = new MailExchange($mailer);

        $exchange->onSending[] = function ($x) use ($mail) {
            Assert::same($mail, $x);
        };
        $exchange->onSuccess[] = function ($x) use ($mail) {
            Assert::same($mail, $x);
        };
        $exchange->onError[] = function ($x) {
            Assert::fail('Error event should not be triggered');
        };

        $exchange->send($mail);
    }

    public function testEvents_Error()
    {
        $mail = $this->mailMock();
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->andThrow(new TestCaseException());

        $exchange = new MailExchange($mailer);

        $exchange->onSending[] = function ($x) use ($mail) {
            Assert::same($mail, $x);
        };
        $exchange->onSuccess[] = function ($x) {
            Assert::fail('Success event should not be triggered');
        };
        $exchange->onError[] = function ($x) use ($mail) {
            Assert::same($mail, $x);
        };

        Assert::exception(function () use ($exchange, $mail) {
            $exchange->send($mail);
        }, TestCaseException::class);
    }

    public function testEvents_ErrorOnThrowable()
    {
        $mail = $this->mailMock();
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send')->andThrow(new \TypeError('type error'));

        $exchange = new MailExchange($mailer);

        $caught = null;
        $exchange->onError[] = function ($x, $ex) use (&$caught) {
            $caught = $ex;
        };

        Assert::exception(fn() => $exchange->send($mail), \TypeError::class);
        Assert::type(\TypeError::class, $caught);
    }

    public function testSend_NoRecipients()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getTo')->andReturn([]);
        $mail->shouldReceive('getCc')->andReturn([]);
        $mail->shouldReceive('getBcc')->andReturn([]);

        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldNotReceive('send');

        $exchange = new MailExchange($mailer);

        $errors = 0;
        $exchange->onError[] = function () use (&$errors) {
            $errors++;
        };

        Assert::exception(fn() => $exchange->send($mail), InvalidStateException::class, 'Mail has no recipients (to, cc or bcc)');
        Assert::equal(1, $errors);
    }

    public function testCreateMessage_PlainGeneratedFromHtml()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getPlainBody')->andReturn('');
        $mail->shouldReceive('getHtmlBody')->andReturn('<p>Hello <b>world</b></p>');

        $message = (new MailExchange(\Mockery::mock(Mailer::class)))->createMessage($mail);

        Assert::contains('Hello', $message->getBody());
        Assert::notContains('<b>', $message->getBody());
    }

    public function testCreateMessage_SubjectFromTitle()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getSubject')->andReturn('');
        $mail->shouldReceive('getHtmlBody')->andReturn('<html><head><title>Title subject</title></head><body>x</body></html>');

        $message = (new MailExchange(\Mockery::mock(Mailer::class)))->createMessage($mail);

        Assert::equal('Title subject', $message->getSubject());
    }

    public function testCreateMessage_EmbeddedImages()
    {
        $mail = $this->mailMock();
        $mail->shouldReceive('getHtmlBody')->andReturn('<p><img src="logo.png"></p>');
        $mail->shouldReceive('getBasePath')->andReturn(FIXTURES_DIR . '/templates/embed');

        $message = (new MailExchange(\Mockery::mock(Mailer::class)))->createMessage($mail);

        Assert::match('~<img src="cid:[^"]+">~', $message->getHtmlBody());
        Assert::contains('Content-Disposition: inline', $message->generateMessage());
    }

    public function testSend_DoesNotChangeDefaultStreamContext()
    {
        $before = stream_context_get_options(stream_context_get_default());

        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send');

        (new MailExchange($mailer))->send($this->mailMock());

        Assert::equal($before, stream_context_get_options(stream_context_get_default()));
    }

    public function testVerifySsl_Deprecated()
    {
        $exchange = new MailExchange(\Mockery::mock(Mailer::class));

        Assert::error(fn() => $exchange->setVerifySsl(false), E_USER_DEPRECATED);
        Assert::noError(fn() => $exchange->setVerifySsl(true));
        Assert::true($exchange->isVerifySsl());
    }

    protected function tearDown()
    {
        \Mockery::close();
    }
}

(new MailExchangeTest())->run();
