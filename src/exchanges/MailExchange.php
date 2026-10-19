<?php

namespace Varhall\Mailino\Exchanges;

use Nette\InvalidStateException;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Varhall\Mailino\Entities\IMail;

class MailExchange extends Exchange
{
    private Mailer $mailer;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
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
        $this->onSending($mail);

        try {
            $this->mailer->send($this->createMessage($mail));
            $this->onSuccess($mail);
        } catch (\Throwable $ex) {
            $this->onError($mail, $ex);
            throw $ex;
        }
    }

    /**
     * Converts mail to Nette message without sending it
     */
    public function createMessage(IMail $mail): Message
    {
        if (empty($mail->getTo()) && empty($mail->getCc()) && empty($mail->getBcc())) {
            throw new InvalidStateException('Mail has no recipients (to, cc or bcc)');
        }

        $message = (new Message())
            ->setFrom($mail->getFrom()->getEmail(), $mail->getFrom()->getName());

        // when subject is empty, Nette uses <title> from HTML body (e.g. <mj-title> in MJML)
        $subject = $mail->getSubject();
        if ($subject !== '') {
            $message->setSubject($subject);
        }

        // plain body is set first, Nette generates plain text from HTML when it is empty
        $message->setBody($mail->getPlainBody())
            ->setHtmlBody($mail->getHtmlBody(), $mail->getBasePath());

        foreach ($mail->getTo() as $recipient) {
            $message->addTo($recipient->getEmail(), $recipient->getName());
        }

        foreach ($mail->getCc() as $recipient) {
            $message->addCc($recipient->getEmail(), $recipient->getName());
        }

        foreach ($mail->getBcc() as $recipient) {
            $message->addBcc($recipient->getEmail(), $recipient->getName());
        }

        foreach ($mail->getFiles() as $file) {
            $message->addAttachment($file->getName(), $file->getContent(), $file->getContentType());
        }

        foreach ($mail->getReplyTo() as $recipient) {
            $message->addReplyTo($recipient->getEmail(), $recipient->getName());
        }

        if ($mail->getReturnPath() !== null) {
            $message->setReturnPath($mail->getReturnPath());
        }

        if ($mail->getPriority() !== null) {
            $message->setPriority($mail->getPriority());
        }

        foreach ($mail->getHeaders() as $name => $value) {
            $message->setHeader($name, $value);
        }

        return $message;
    }
}
