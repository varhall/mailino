<?php

declare(strict_types=1);

namespace Varhall\Mailino\Queue;

use Varhall\Mailino\Exchanges\IExchange;

/**
 * Sends mail from queue payload using real exchange (e.g. MailExchange)
 */
class QueueWorker
{
    private MailSerializer $serializer;

    public function __construct(
        private readonly IExchange $exchange,
        ?MailSerializer $serializer = null
    ) {
        if ($exchange instanceof QueueExchange) {
            throw new \LogicException('Queue worker must send mails using real exchange, not ' . QueueExchange::class);
        }

        $this->serializer = $serializer ?? new MailSerializer();
    }

    public function handle(string $payload): void
    {
        $this->exchange->send($this->serializer->unserialize($payload));
    }
}
