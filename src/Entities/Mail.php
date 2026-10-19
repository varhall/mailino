<?php

declare(strict_types=1);

namespace Varhall\Mailino\Entities;

use Nette\Http\FileUpload;

/**
 * Mail definition - template, data, recipients, attachments, headers and extensions.
 * Holds data only (no DI container, no template engine), rendering is done by MailRenderer.
 */
class Mail
{
    protected string $template;

    /** @var array<string, mixed> */
    protected array $data;

    protected ?string $subject = null;

    protected ?Recipient $from = null;

    /** @var list<Recipient> */
    protected array $to = [];

    /** @var list<Recipient> */
    protected array $cc = [];

    /** @var list<Recipient> */
    protected array $bcc = [];

    /** @var list<Recipient> */
    protected array $replyTo = [];

    /** @var list<Attachment> */
    protected array $files = [];

    protected ?string $returnPath = null;

    protected ?int $priority = null;

    /** @var array<string, string> */
    protected array $headers = [];

    protected ?string $basePath = null;

    /** @var list<class-string> */
    protected array $extensions = [];


    /**
     * @param string $template  template path relative to template directory, without extension
     * @param array<string, mixed> $data  template parameters
     */
    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }


    /// TEMPLATE

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Explicitly set subject. When null, subject is rendered from template
     * ({define subject} block, <title> or <mj-title>).
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }


    /// SENDER & RECIPIENTS

    public function getFrom(): ?Recipient
    {
        return $this->from;
    }

    public function setFrom(string $email, ?string $name = null): static
    {
        $this->from = new Recipient($email, $name);
        return $this;
    }

    /**
     * @return list<Recipient>
     */
    public function getTo(): array
    {
        return $this->to;
    }

    public function addTo(string $email, ?string $name = null): static
    {
        $this->to[] = new Recipient($email, $name);
        return $this;
    }

    public function clearTo(): static
    {
        $this->to = [];
        return $this;
    }

    /**
     * @return list<Recipient>
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    public function addCc(string $email, ?string $name = null): static
    {
        $this->cc[] = new Recipient($email, $name);
        return $this;
    }

    public function clearCc(): static
    {
        $this->cc = [];
        return $this;
    }

    /**
     * @return list<Recipient>
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function addBcc(string $email, ?string $name = null): static
    {
        $this->bcc[] = new Recipient($email, $name);
        return $this;
    }

    public function clearBcc(): static
    {
        $this->bcc = [];
        return $this;
    }

    /**
     * @return list<Recipient>
     */
    public function getReplyTo(): array
    {
        return $this->replyTo;
    }

    public function addReplyTo(string $email, ?string $name = null): static
    {
        $this->replyTo[] = new Recipient($email, $name);
        return $this;
    }

    public function clearReplyTo(): static
    {
        $this->replyTo = [];
        return $this;
    }


    /// ATTACHMENTS

    /**
     * @return list<Attachment>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param Attachment|FileUpload|\SplFileInfo|string $file  attachment, upload or local file path (never user input)
     */
    public function addFile(Attachment|FileUpload|\SplFileInfo|string $file, ?string $name = null): static
    {
        $this->files[] = match (true) {
            $file instanceof Attachment     => $name !== null ? $file->withName($name) : $file,
            $file instanceof FileUpload     => Attachment::fromUpload($file, $name),
            $file instanceof \SplFileInfo   => Attachment::fromFile($file, $name),
            default                         => Attachment::fromPath($file, $name),
        };

        return $this;
    }

    public function clearFiles(): static
    {
        $this->files = [];
        return $this;
    }


    /// HEADERS

    public function getReturnPath(): ?string
    {
        return $this->returnPath;
    }

    public function setReturnPath(?string $email): static
    {
        $this->returnPath = $email;
        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): static
    {
        $this->priority = $priority;
        return $this;
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

    public function addHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function removeHeader(string $name): static
    {
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * Directory used to embed local images referenced in HTML body as inline attachments
     */
    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    public function setBasePath(?string $path): static
    {
        $this->basePath = $path;
        return $this;
    }


    /// EXTENSIONS

    /**
     * Adds extension applied when mail is rendered. Extensions are applied in order they were added,
     * adding the same extension again has no effect.
     *
     * @param class-string $class
     */
    public function extend(string $class): static
    {
        if (!in_array($class, $this->extensions, true)) {
            $this->extensions[] = $class;
        }

        return $this;
    }

    /**
     * @return list<class-string>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @param class-string $class
     */
    public function hasExtension(string $class): bool
    {
        return in_array($class, $this->extensions, true);
    }

    /**
     * @param class-string $class
     */
    public function removeExtension(string $class): static
    {
        $this->extensions = array_values(array_filter($this->extensions, fn(string $item) => $item !== $class));
        return $this;
    }
}
