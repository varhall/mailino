<?php

declare(strict_types=1);

namespace Varhall\Mailino\Queue;

use Nette\SmartObject;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Exchanges\IExchange;

/**
 * Pushes mail into queue instead of sending it. Queue consumer passes payload to QueueWorker.
 *
 * @method void onQueued(Mail $mail)
 */
class QueueExchange implements IExchange
{
    use SmartObject;

    /** @var array<callable(Mail): void> */
    public array $onQueued = [];

    private MailSerializer $serializer;

    public function __construct(
        private readonly IMailQueue $queue,
        ?MailSerializer $serializer = null
    ) {
        $this->serializer = $serializer ?? new MailSerializer();
    }

    public function send(Mail $mail): void
    {
        $this->queue->push($this->serializer->serialize($mail));
        $this->onQueued($mail);
    }
}
