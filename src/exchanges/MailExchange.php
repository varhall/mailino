<?php

namespace Varhall\Mailino\Exchanges;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Varhall\Mailino\Entities\IMail;

class MailExchange extends Exchange
{
    /** @var Mailer */
    private $mailer;

    /** @var bool */
    private $verifySsl = false;

    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function isVerifySsl(): bool
    {
        return $this->verifySsl;
    }

    public function setVerifySsl(bool $verifySsl): void
    {
        $this->verifySsl = $verifySsl;
    }

    public function send(IMail $mail): void
    {
        $this->onSending($mail);

        try {
            $message = (new Message())
                ->setFrom($mail->getFrom()->getEmail(), $mail->getFrom()->getName())
                ->setSubject($mail->getSubject())
                ->setBody($mail->getPlainBody())
                ->setHtmlBody($mail->getHtmlBody());

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

            if (!$this->verifySsl)
                $this->disableSSLVerification();

            $this->mailer->send($message);
            $this->onSuccess($mail);

        } catch (\Exception $ex) {
            $this->onError($mail, $ex);
            throw $ex;
        }
    }

    protected function disableSSLVerification()
    {
        stream_context_set_default([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);
    }
}