<?php

namespace Tests\Mailino\Queue;

use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Tests\Engine\Helpers;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Exchanges\NullExchange;
use Varhall\Mailino\Exchanges\IExchange;
use Varhall\Mailino\Extensions\Prefix;
use Varhall\Mailino\Mailino;
use Varhall\Mailino\Queue\IMailQueue;
use Varhall\Mailino\Queue\MailSerializer;
use Varhall\Mailino\Queue\QueueExchange;
use Varhall\Mailino\Queue\QueueWorker;

require_once __DIR__ . '/../../bootstrap.php';

class QueueTest extends ContainerTestCase
{
    private function createMail(): Mail
    {
        return (new Mail('subject/test', [ 'name' => 'Pepa', 'items' => [ 1, 2.5, true, null, [ 'nested' => 'ok' ] ] ]))
            ->setSubject('Subject')
            ->setFrom('from@test.cz', 'From')
            ->addTo('to@test.cz', 'To')
            ->addTo('to2@test.cz')
            ->addCc('cc@test.cz')
            ->addBcc('bcc@test.cz')
            ->addReplyTo('reply@test.cz', 'Reply')
            ->addFile(new Attachment("binary \x00\xFF content", 'application/octet-stream', 'file.bin'))
            ->setReturnPath('bounce@test.cz')
            ->setPriority(2)
            ->addHeader('X-A', 'a')
            ->setBasePath('/tmp/images')
            ->extend(Prefix::class);
    }

    public function testSerializer_RoundTrip()
    {
        $serializer = new MailSerializer();
        $mail = $this->createMail();

        $payload = $serializer->serialize($mail);

        Assert::type('string', $payload);
        Assert::equal($mail, $serializer->unserialize($payload));
    }

    public function testSerializer_ObjectInData()
    {
        $mail = new Mail('a', [ 'user' => [ 'profile' => new \stdClass() ] ]);

        Assert::exception(
            fn() => (new MailSerializer())->serialize($mail),
            InvalidArgumentException::class,
            "Template data.user.profile of type stdClass can't be sent through queue, pass scalars or arrays instead of objects"
        );
    }

    public function testSerializer_InvalidPayload()
    {
        Assert::exception(fn() => (new MailSerializer())->unserialize('{"version": 999}'), InvalidArgumentException::class);
        Assert::exception(fn() => (new MailSerializer())->unserialize('not json'), \JsonException::class);
    }

    public function testQueueExchange()
    {
        $queue = new MemoryQueue();
        $exchange = new QueueExchange($queue);

        $queued = 0;
        $exchange->onQueued[] = function () use (&$queued) {
            $queued++;
        };

        $exchange->send($this->createMail());

        Assert::count(1, $queue->payloads);
        Assert::equal(1, $queued);
    }

    public function testQueueExchange_InvalidData()
    {
        $queue = new MemoryQueue();

        Assert::exception(fn() => (new QueueExchange($queue))->send(new Mail('a', [ 'x' => new \stdClass() ])), InvalidArgumentException::class);
        Assert::count(0, $queue->payloads);
    }

    public function testWorker()
    {
        $queue = new MemoryQueue();
        (new QueueExchange($queue))->send($this->createMail()->removeExtension(Prefix::class));

        $sender = new NullExchange(Helpers::renderer());
        (new QueueWorker($sender))->handle($queue->payloads[0]);

        Assert::count(1, $sender->getMessages());
        Assert::equal('Subject', $sender->getMessages()[0]->getSubject());
        Assert::equal([ 'to@test.cz' => 'To', 'to2@test.cz' => null ], $sender->getMessages()[0]->getHeader('To'));
    }

    public function testContainer()
    {
        $container = $this->createContainer(CONFIG_DIR . '/queue.neon');

        $exchange = $container->getByType(IExchange::class);
        Assert::type(QueueExchange::class, $exchange);

        $mail = $container->getByType(Mailino::class)->create('subject/test', [ 'name' => 'Pepa' ])->addTo('to@test.cz');
        $exchange->send($mail);

        $queue = $container->getByType(MemoryQueue::class);
        Assert::count(1, $queue->payloads);

        $worker = $container->getByType(QueueWorker::class);
        $sender = (fn() => $this->exchange)->call($worker);
        $worker->handle($queue->payloads[0]);

        Assert::type(NullExchange::class, $sender);
        Assert::count(1, $sender->getMessages());
        Assert::equal('Hello Pepa & co', $sender->getMessages()[0]->getSubject());
    }

    public function testWorker_RejectsQueueExchange()
    {
        Assert::exception(fn() => new QueueWorker(new QueueExchange(new MemoryQueue())), \LogicException::class);
    }
}

class MemoryQueue implements IMailQueue
{
    /** @var string[] */
    public array $payloads = [];

    public function push(string $payload): void
    {
        $this->payloads[] = $payload;
    }
}

(new QueueTest())->run();
