<?php

declare(strict_types=1);

namespace Varhall\Mailino\Rendering;

use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Entities\Recipient;

/**
 * Immutable rendered mail - final subject and bodies together with envelope data.
 * Extensions transform it using with*() methods.
 */
final class RenderedMail
{
    /**
     * @param list<Recipient> $to
     * @param list<Recipient> $cc
     * @param list<Recipient> $bcc
     * @param list<Recipient> $replyTo
     * @param list<Attachment> $files
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $template,
        private readonly string $subject,
        private readonly string $htmlBody,
        private readonly string $plainBody,
        private readonly ?Recipient $from = null,
        private readonly array $to = [],
        private readonly array $cc = [],
        private readonly array $bcc = [],
        private readonly array $replyTo = [],
        private readonly array $files = [],
        private readonly ?string $returnPath = null,
        private readonly ?int $priority = null,
        private readonly array $headers = [],
        private readonly ?string $basePath = null
    ) {
    }

    public static function fromMail(Mail $mail, string $subject, string $htmlBody, string $plainBody): self
    {
        return new self(
            $mail->getTemplate(),
            $subject,
            $htmlBody,
            $plainBody,
            $mail->getFrom(),
            $mail->getTo(),
            $mail->getCc(),
            $mail->getBcc(),
            $mail->getReplyTo(),
            $mail->getFiles(),
            $mail->getReturnPath(),
            $mail->getPriority(),
            $mail->getHeaders(),
            $mail->getBasePath()
        );
    }


    /// GETTERS

    public function getTemplate(): string
    {
        return $this->template;
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

    public function getFrom(): ?Recipient
    {
        return $this->from;
    }

    /**
     * @return list<Recipient>
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @return list<Recipient>
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * @return list<Recipient>
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * @return list<Recipient>
     */
    public function getReplyTo(): array
    {
        return $this->replyTo;
    }

    /**
     * @return list<Attachment>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function getReturnPath(): ?string
    {
        return $this->returnPath;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getBasePath(): ?string
    {
        return $this->basePath;
    }


    /// WITHERS

    public function withSubject(string $subject): self
    {
        return $this->with([ 'subject' => $subject ]);
    }

    public function withHtmlBody(string $htmlBody): self
    {
        return $this->with([ 'htmlBody' => $htmlBody ]);
    }

    public function withPlainBody(string $plainBody): self
    {
        return $this->with([ 'plainBody' => $plainBody ]);
    }

    public function withFrom(?Recipient $from): self
    {
        return $this->with([ 'from' => $from ]);
    }

    /**
     * @param Recipient[] $to
     */
    public function withTo(array $to): self
    {
        return $this->with([ 'to' => array_values($to) ]);
    }

    /**
     * @param Recipient[] $cc
     */
    public function withCc(array $cc): self
    {
        return $this->with([ 'cc' => array_values($cc) ]);
    }

    /**
     * @param Recipient[] $bcc
     */
    public function withBcc(array $bcc): self
    {
        return $this->with([ 'bcc' => array_values($bcc) ]);
    }

    /**
     * @param Recipient[] $replyTo
     */
    public function withReplyTo(array $replyTo): self
    {
        return $this->with([ 'replyTo' => array_values($replyTo) ]);
    }

    /**
     * @param Attachment[] $files
     */
    public function withFiles(array $files): self
    {
        return $this->with([ 'files' => array_values($files) ]);
    }

    public function withHeader(string $name, ?string $value): self
    {
        $headers = $this->headers;

        if ($value === null) {
            unset($headers[$name]);
        } else {
            $headers[$name] = $value;
        }

        return $this->with([ 'headers' => $headers ]);
    }

    public function withReturnPath(?string $returnPath): self
    {
        return $this->with([ 'returnPath' => $returnPath ]);
    }

    public function withPriority(?int $priority): self
    {
        return $this->with([ 'priority' => $priority ]);
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function with(array $changes): self
    {
        $values = get_object_vars($this);

        return new self(...array_merge($values, $changes));
    }
}
