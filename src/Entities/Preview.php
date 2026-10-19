<?php

namespace Varhall\Mailino\Entities;

use Nette\Mail\Message;

/**
 * Rendered mail content (subject, HTML and plain text) e.g. for preview in administration
 */
/**
 * @phpstan-consistent-constructor
 */
class Preview
{
    private string $subject;

    private string $htmlBody;

    private string $plainBody;


    public function __construct(string $subject, string $htmlBody, string $plainBody)
    {
        $this->subject = $subject;
        $this->htmlBody = $htmlBody;
        $this->plainBody = $plainBody;
    }

    public static function fromMail(IMail $mail): self
    {
        $html = $mail->getHtmlBody();
        $plain = $mail->getPlainBody();
        $subject = $mail->getSubject();

        // generate missing plain text and subject (from <title>) from HTML the same way as when sending
        $message = (new Message())->setHtmlBody($html);

        return new static(
            $subject !== '' ? $subject : (string) $message->getSubject(),
            $html,
            $plain !== '' ? $plain : $message->getBody()
        );
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getHtmlBody(): string
    {
        return $this->htmlBody;
    }

    public function getPlainBody(): string
    {
        return $this->plainBody;
    }
}
