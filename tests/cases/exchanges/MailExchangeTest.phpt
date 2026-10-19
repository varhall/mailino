<?php

namespace Tests\Mailino\Exchanges;

use Nette\InvalidStateException;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Engine\Helpers;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Exchanges\MailExchange;
use Varhall\Mailino\Rendering\RenderedMail;

require_once __DIR__ . '/../../bootstrap.php';

class MailExchangeTest extends BaseTestCase
{
    private function createMail(): Mail
    {
        return (new Mail('subject/test', [ 'name' => 'Pepa' ]))
            ->setFrom('from@test.cz', 'From')
            ->addTo('to1@test.cz', 'To1')
            ->addTo('to2@test.cz')
            ->addCc('cc@test.cz', 'Cc')
            ->addBcc('bcc@test.cz', 'Bcc')
            ->addReplyTo('reply@test.cz', 'Reply')
            ->addFile(new Attachment('content 1', 'text/plain', 'attachment1.txt'))
            ->setReturnPath('bounce@test.cz')
            ->setPriority(1)
            ->addHeader('X-Custom', 'my-value');
    }

    /**
     * @return array{MailExchange, \Closure(): ?Message}
     */
    private function createExchange(?\Throwable $error = null): array
    {
        $sent = null;
        $mailer = \Mockery::mock(Mailer::class);
        $send = $mailer->shouldReceive('send');

        $error !== null
            ? $send->andThrow($error)
            : $send->andReturnUsing(function (Message $message) use (&$sent) {
                $sent = $message;
            });

        return [ new MailExchange($mailer, Helpers::renderer()), function () use (&$sent) {
            return $sent;
        } ];
    }

    public function testSend()
    {
        [ $exchange, $sent ] = $this->createExchange();
        $exchange->send($this->createMail());

        $message = $sent();
        Assert::type(Message::class, $message);
        Assert::equal([ 'from@test.cz' => 'From' ], $message->getFrom());
        Assert::equal('Hello Pepa & co', $message->getSubject());
        Assert::equal([ 'to1@test.cz' => 'To1', 'to2@test.cz' => null ], $message->getHeader('To'));
        Assert::equal([ 'cc@test.cz' => 'Cc' ], $message->getHeader('Cc'));
        Assert::equal([ 'bcc@test.cz' => 'Bcc' ], $message->getHeader('Bcc'));
        Assert::equal([ 'reply@test.cz' => 'Reply' ], $message->getHeader('Reply-To'));
        Assert::equal('bounce@test.cz', $message->getReturnPath());
        Assert::equal(1, $message->getPriority());
        Assert::equal('my-value', $message->getHeader('X-Custom'));
        Assert::contains('<p>Hi Pepa</p>', $message->getHtmlBody());
        Assert::contains('Hi Pepa', $message->getBody());
        Assert::count(1, $message->getAttachments());
    }

    public function testEvents_Success()
    {
        [ $exchange ] = $this->createExchange();
        $mail = $this->createMail();

        $events = [];
        $exchange->onSending[] = function (Mail $x) use (&$events, $mail) {
            Assert::same($mail, $x);
            $events[] = 'sending';
        };
        $exchange->onSuccess[] = function (Mail $x, RenderedMail $rendered) use (&$events, $mail) {
            Assert::same($mail, $x);
            Assert::equal('Hello Pepa & co', $rendered->getSubject());
            $events[] = 'success';
        };
        $exchange->onError[] = fn() => Assert::fail('Error event should not be triggered');

        $exchange->send($mail);

        Assert::equal([ 'sending', 'success' ], $events);
    }

    public function testEvents_Error()
    {
        [ $exchange ] = $this->createExchange(new \TypeError('type error'));
        $mail = $this->createMail();

        $caught = null;
        $exchange->onSuccess[] = fn() => Assert::fail('Success event should not be triggered');
        $exchange->onError[] = function (Mail $x, \Throwable $ex) use (&$caught, $mail) {
            Assert::same($mail, $x);
            $caught = $ex;
        };

        Assert::exception(fn() => $exchange->send($mail), \TypeError::class);
        Assert::type(\TypeError::class, $caught);
    }

    public function testSend_NoRecipients()
    {
        [ $exchange, $sent ] = $this->createExchange();

        $errors = 0;
        $exchange->onError[] = function () use (&$errors) {
            $errors++;
        };

        Assert::exception(
            fn() => $exchange->send($this->createMail()->clearTo()->clearCc()->clearBcc()),
            InvalidStateException::class,
            'Mail has no recipients (to, cc or bcc)'
        );
        Assert::equal(1, $errors);
        Assert::null($sent());
    }

    public function testSend_NoSender()
    {
        [ $exchange ] = $this->createExchange();

        Assert::exception(fn() => $exchange->send((new Mail('subject/test', [ 'name' => 'x' ]))->addTo('to@test.cz')), InvalidStateException::class, 'Sender (from) is not set');
    }

    public function testSend_EmbeddedImages()
    {
        [ $exchange, $sent ] = $this->createExchange();

        $exchange->send($this->createMail()->setTemplate('embed/test')->setBasePath(FIXTURES_DIR . '/templates/embed'));

        Assert::match('~<img src="cid:[^"]+">~', $sent()->getHtmlBody());
        Assert::contains('Content-Disposition: inline', $sent()->generateMessage());
    }

    public function testSend_DoesNotChangeDefaultStreamContext()
    {
        $before = stream_context_get_options(stream_context_get_default());

        [ $exchange ] = $this->createExchange();
        $exchange->send($this->createMail());

        Assert::equal($before, stream_context_get_options(stream_context_get_default()));
    }

    protected function tearDown()
    {
        \Mockery::close();
    }
}

(new MailExchangeTest())->run();
