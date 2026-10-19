<?php

namespace Varhall\Mailino\Exchanges;

use Nette\Mail\Message;
use Varhall\Mailino\Entities\IMail;

/**
 * Does not send anything, only renders messages and keeps them in memory (tests, development)
 */
class NullExchange extends Exchange
{
    private MessageFactory $messageFactory;

    /** @var Message[] */
    private array $messages = [];

    public function __construct(?MessageFactory $messageFactory = null)
    {
        $this->messageFactory = $messageFactory ?? new MessageFactory();
    }

    public function send(IMail $mail): void
    {
        $this->dispatch($mail, function () use ($mail): void {
            $this->messages[] = $this->messageFactory->create($mail);
        });
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->messages = [];
    }
}
