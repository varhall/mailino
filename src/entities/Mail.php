<?php

namespace Varhall\Mailino\Entities;

use Latte\Engine;
use Nette\DI\Container;
use Nette\InvalidArgumentException;
use Nette\NotSupportedException;
use Varhall\Mailino\Extensions\IExtension;

class Mail implements IMail
{
    /** @var Container */
    protected $container;

    /** @var Engine */
    protected $latte;

    /** @var string */
    protected $path;

    /** @var string */
    protected $template;

    /** @var array */
    protected $data = [];

    /** @var string */
    protected $subject;

    /** @var Recipient */
    protected $from;

    /** @var array */
    protected $recipients = [];

    /** @var array */
    protected $files = [];


    public function __construct(Container $container, string $path)
    {
        $this->container = $container;
        $this->latte = $container->getByType(Engine::class);
        $this->path = $path;
    }

    public function getFrom(): Recipient
    {
        return $this->from;
    }

    public function setFrom(string $email, string $name = null): IMail
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

    public function addTo(string $email, string $name = null): IMail
    {
        return $this->addRecipient('to', $email, $name);
    }

    public function getCc(): array
    {
        return $this->getRecipients('cc');
    }

    public function addCc(string $email, string $name = null): IMail
    {
        return $this->addRecipient('cc', $email, $name);
    }

    public function getBcc(): array
    {
        return $this->getRecipients('bcc');
    }

    public function addBcc(string $email, string $name = null): IMail
    {
        return $this->addRecipient('bcc', $email, $name);
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function addFile($file, string $name = null): Mail
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

    public function extend(string $class): IMail
    {
        $factory = $this->container->getByType($class);

        if (!($factory instanceof IExtension))
            throw new InvalidArgumentException("Mail extension '{$class}' does not implement " . IExtension::class);

        return $factory->extend($this);
    }


    /// PRIVATE METHODS

    protected function getRecipients(string $type): array
    {
        return array_key_exists($type, $this->recipients) ? $this->recipients[$type] : [];
    }

    protected function addRecipient(string $type, string $email, string $name): IMail
    {
        $this->recipients[$type][] = new Recipient($email, $name);
        return $this;
    }

    protected function templatePath(string $type): string
    {
        return implode(DIRECTORY_SEPARATOR, [ $this->path, "{$this->template}.{$type}.latte" ]);
    }
}