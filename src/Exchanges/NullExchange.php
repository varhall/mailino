<?php

declare(strict_types=1);

namespace Varhall\Mailino\Exchanges;

use Nette\Mail\Message;
use Varhall\Mailino\Rendering\MailRenderer;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Does not send anything, keeps messages in memory (tests, development)
 */
class NullExchange extends Exchange
{
    private MessageFactory $messageFactory;

    /** @var list<Message> */
    private array $messages = [];

    public function __construct(MailRenderer $renderer, ?MessageFactory $messageFactory = null)
    {
        parent::__construct($renderer);
        $this->messageFactory = $messageFactory ?? new MessageFactory();
    }

    /**
     * @return list<Message>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function clear(): void
    {
        $this->messages = [];
    }

    protected function deliver(RenderedMail $mail): void
    {
        $this->messages[] = $this->messageFactory->create($mail);
    }
}
