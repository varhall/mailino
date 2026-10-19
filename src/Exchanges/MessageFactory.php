<?php

declare(strict_types=1);

namespace Varhall\Mailino\Exchanges;

use Nette\InvalidStateException;
use Nette\Mail\Message;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Converts rendered mail to Nette message
 */
class MessageFactory
{
    public function create(RenderedMail $mail): Message
    {
        $from = $mail->getFrom();

        if ($from === null) {
            throw new InvalidStateException('Sender (from) is not set');
        }

        if (empty($mail->getTo()) && empty($mail->getCc()) && empty($mail->getBcc())) {
            throw new InvalidStateException('Mail has no recipients (to, cc or bcc)');
        }

        $message = (new Message())
            ->setFrom($from->getEmail(), $from->getName())
            ->setSubject($mail->getSubject())
            ->setBody($mail->getPlainBody())
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

        foreach ($mail->getReplyTo() as $recipient) {
            $message->addReplyTo($recipient->getEmail(), $recipient->getName());
        }

        foreach ($mail->getFiles() as $file) {
            $message->addAttachment($file->getName(), $file->getContent(), $file->getContentType());
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
