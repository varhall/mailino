<?php

namespace Varhall\Mailino\Exchanges;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Varhall\Mailino\Entities\IMail;

class MailExchange extends Exchange
{
    private Mailer $mailer;

    private MessageFactory $messageFactory;

    public function __construct(Mailer $mailer, ?MessageFactory $messageFactory = null)
    {
        $this->mailer = $mailer;
        $this->messageFactory = $messageFactory ?? new MessageFactory();
    }

    /**
     * @deprecated SSL verification is always enabled, configure SMTP stream context instead (mail: context: ssl: ...)
     */
    public function isVerifySsl(): bool
    {
        return true;
    }

    /**
     * @deprecated SSL verification is no longer disabled globally. To disable it for SMTP connection only,
     *             configure stream context in Nette mail extension: mail: context: ssl: verify_peer: false
     */
    public function setVerifySsl(bool $verifySsl): void
    {
        if (!$verifySsl) {
            trigger_error(__METHOD__ . '(false) has no effect, configure SSL options in SMTP stream context (mail: context: ssl: ...)', E_USER_DEPRECATED);
        }
    }

    public function send(IMail $mail): void
    {
        $this->dispatch($mail, fn() => $this->mailer->send($this->createMessage($mail)));
    }

    /**
     * Converts mail to Nette message without sending it
     */
    public function createMessage(IMail $mail): Message
    {
        return $this->messageFactory->create($mail);
    }
}
