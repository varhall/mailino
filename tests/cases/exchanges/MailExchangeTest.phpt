<?php

namespace Tests\Mailino;

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
        $mail->shouldReceive('getSubject')->andReturn('subject');
        $mail->shouldReceive('getPlainBody')->andReturn('plain');
        $mail->shouldReceive('getHtmlBody')->andReturn('html');
        $mail->shouldReceive('getTo')->andReturn([
            new Recipient('to1@test.cz', 'To1'),
            new Recipient('to2@test.cz', 'To2')
        ]);
        $mail->shouldReceive('getCc')->andReturn([
            new Recipient('cc1@test.cz', 'Cc'),
            new Recipient('cc2@test.cz', 'Cc')
        ]);
        $mail->shouldReceive('getBcc')->andReturn([
            new Recipient('bcc1@test.cz', 'Bcc1'),
            new Recipient('bcc2@test.cz', 'Bcc2')
        ]);
        $mail->shouldReceive('getFiles')->andReturn([
            new Attachment('content 1', 'text/plain', 'attachment1.txt'),
            new Attachment('content 2', 'text/plain', 'attachment2.txt')
        ]);

        return $mail;
    }

    public function testSend()
    {
        $mailer = \Mockery::mock(Mailer::class);
        $mailer->shouldReceive('send');

        $exchange = new MailExchange($mailer);
        $exchange->send($this->mailMock());
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

        Assert::exception(function() use ($exchange, $mail) {
            $exchange->send($mail);
        }, TestCaseException::class);
    }
}

(new MailExchangeTest())->run();
