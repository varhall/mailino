<?php

declare(strict_types=1);

namespace Varhall\Mailino\Exchanges;

use Nette\Mail\Mailer;
use Varhall\Mailino\Rendering\MailRenderer;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Sends mail using Nette mailer
 */
class MailExchange extends Exchange
{
    private MessageFactory $messageFactory;

    public function __construct(
        private readonly Mailer $mailer,
        MailRenderer $renderer,
        ?MessageFactory $messageFactory = null
    ) {
        parent::__construct($renderer);
        $this->messageFactory = $messageFactory ?? new MessageFactory();
    }

    protected function deliver(RenderedMail $mail): void
    {
        $this->mailer->send($this->messageFactory->create($mail));
    }
}
