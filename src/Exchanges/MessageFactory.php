<?php

namespace Varhall\Mailino\Exchanges;

use Nette\InvalidStateException;
use Nette\Mail\Message;
use Varhall\Mailino\Entities\IMail;

/**
 * Converts mail to Nette message (renders templates and applies extensions)
 */
class MessageFactory
{
    public function create(IMail $mail): Message
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
