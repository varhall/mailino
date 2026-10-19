<?php

declare(strict_types=1);

namespace Varhall\Mailino\Queue;

/**
 * Adapter to application queue (Symfony Messenger, RabbitMQ, database table...).
 * Payload is JSON string created by MailSerializer, worker passes it to QueueWorker::handle().
 */
interface IMailQueue
{
    public function push(string $payload): void;
}
