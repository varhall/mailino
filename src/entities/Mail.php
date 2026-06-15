<?php

namespace Varhall\Mailino\Entities;

use Latte\Engine;
use Nette\DI\Container;
use Nette\InvalidArgumentException;
use Nette\NotSupportedException;
use Varhall\Mailino\Extensions\IExtension;

class Mail implements IMail
{
    protected Container $container;

    protected Engine $latte;

    protected string $path;

    protected $template;

    protected array $data = [];

    protected string $subject;

    protected Recipient $from;

    protected array $recipients = [];

    protected array $files = [];

    protected ?string $returnPath = null;

    protected ?int $priority = null;

    protected array $headers = [];


    public function __construct(Engine $latte, Container $container, string $path)
    {
        $this->latte = $latte;
        $this->container = $container;
        $this->path = $path;
    }

    public function getFrom(): Recipient
    {
        return $this->from;
    }

    public function setFrom(string $email, ?string $name = null): IMail
    {
        $this->from = new Recipient($email, $name);
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): IMail
    {
        $this->subject = $subject;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): IMail
    {
        $this->data = $data;
        return $this;
    }

    public function getHtmlBody(): string
    {
        return $this->latte->renderToString($this->templatePath('html'), $this->data);
    }

    public function getPlainBody(): string
    {
        return $this->latte->renderToString($this->templatePath('plain'), $this->data);
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): IMail
    {
        $this->template = $template;
        return $this;
    }

    public function getTo(): array
    {
        return $this->getRecipients('to');
    }

    public function addTo(string $email, ?string $name = null): IMail
    {
        return $this->addRecipient('to', $email, $name);
    }

    public function getCc(): array
    {
        return $this->getRecipients('cc');
    }

    public function addCc(string $email, ?string $name = null): IMail
    {
        return $this->addRecipient('cc', $email, $name);
    }

    public function getBcc(): array
    {
        return $this->getRecipients('bcc');
    }

    public function addBcc(string $email, ?string $name = null): IMail
    {
        return $this->addRecipient('bcc', $email, $name);
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function addFile($file, ?string $name = null): Mail
    {
        if ($file instanceof \Nette\Http\FileUpload) {
            $this->files[] = Attachment::fromUpload($file, $name);

        } else if (is_string($file)) {
            $this->files[] = Attachment::fromPath($file, $name);

        } else if ($file instanceof \SplFileInfo) {
            $this->files[] = Attachment::fromFile($file, $name);

        } else {
            throw new NotSupportedException('Unsupported attachment type');
        }

        return $this;
    }

    public function getReplyTo(): array
    {
        return $this->getRecipients('replyTo');
    }

    public function addReplyTo(string $email, ?string $name = null): IMail
    {
        return $this->addRecipient('replyTo', $email, $name);
    }

    public function getReturnPath(): ?string
    {
        return $this->returnPath;
    }

    public function setReturnPath(string $email): IMail
    {
        $this->returnPath = $email;
        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): IMail
    {
        $this->priority = $priority;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function addHeader(string $name, string $value): IMail
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function extend(string $class): IMail
    {
        return $this->getExtensionFactory($class)->extend($this);
    }

    public function getExtensionFactory(string $class): IExtension
    {
        $factory = $this->container->getByType($class);

        if (!($factory instanceof IExtension))
            throw new InvalidArgumentException("Mail extension '{$class}' does not implement " . IExtension::class);

        return $factory;
    }


    /// PRIVATE METHODS

    protected function getRecipients(string $type): array
    {
        return array_key_exists($type, $this->recipients) ? $this->recipients[$type] : [];
    }

    protected function addRecipient(string $type, string $email, ?string $name): IMail
    {
        $this->recipients[$type][] = new Recipient($email, $name);
        return $this;
    }

    protected function templatePath(string $type): string
    {
        return implode(DIRECTORY_SEPARATOR, [ $this->path, "{$this->template}.{$type}.latte" ]);
    }
}