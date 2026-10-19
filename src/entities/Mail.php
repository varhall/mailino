<?php

namespace Varhall\Mailino\Entities;

use Latte\ContentType;
use Latte\Engine;
use Nette\DI\Container;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\NotSupportedException;
use Varhall\Mailino\Extensions\IExtension;

class Mail implements IMail
{
    public const SUBJECT_BLOCK = 'subject';

    protected Container $container;

    protected Engine $latte;

    protected string $path;

    protected bool $strict;

    protected string $template = '';

    protected array $data = [];

    protected ?string $subject = null;

    protected ?Recipient $from = null;

    protected array $recipients = [];

    protected array $files = [];

    protected ?string $returnPath = null;

    protected ?int $priority = null;

    protected array $headers = [];

    protected ?string $basePath = null;


    /**
     * @param bool $strict  throw exception on warnings (e.g. undefined variable) during template rendering
     */
    public function __construct(Engine $latte, Container $container, string $path, bool $strict = false)
    {
        $this->latte = $latte;
        $this->container = $container;
        $this->path = $path;
        $this->strict = $strict;
    }

    public function getFrom(): Recipient
    {
        if ($this->from === null) {
            throw new InvalidStateException('Sender (from) is not set');
        }

        return $this->from;
    }

    public function setFrom(string $email, ?string $name = null): IMail
    {
        $this->from = new Recipient($email, $name);
        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject ?? $this->renderSubject();
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
        return $this->render($this->templatePath('html'), ContentType::Html);
    }

    public function getPlainBody(): string
    {
        $path = $this->templatePath('plain');

        if (!is_file($path)) {
            return '';
        }

        return $this->render($path, ContentType::Text);
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

    public function clearTo(): IMail
    {
        return $this->clearRecipients('to');
    }

    public function getCc(): array
    {
        return $this->getRecipients('cc');
    }

    public function addCc(string $email, ?string $name = null): IMail
    {
        return $this->addRecipient('cc', $email, $name);
    }

    public function clearCc(): IMail
    {
        return $this->clearRecipients('cc');
    }

    public function getBcc(): array
    {
        return $this->getRecipients('bcc');
    }

    public function addBcc(string $email, ?string $name = null): IMail
    {
        return $this->addRecipient('bcc', $email, $name);
    }

    public function clearBcc(): IMail
    {
        return $this->clearRecipients('bcc');
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param mixed $file  string path, \SplFileInfo, \Nette\Http\FileUpload or Attachment
     */
    public function addFile($file, ?string $name = null): Mail
    {
        if ($file instanceof Attachment) {
            if ($name !== null) {
                $file->setName($name);
            }

            $this->files[] = $file;
        } elseif ($file instanceof \Nette\Http\FileUpload) {
            $this->files[] = Attachment::fromUpload($file, $name);
        } elseif (is_string($file)) {
            $this->files[] = Attachment::fromPath($file, $name);
        } elseif ($file instanceof \SplFileInfo) {
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

    public function clearReplyTo(): IMail
    {
        return $this->clearRecipients('replyTo');
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

    public function removeHeader(string $name): IMail
    {
        unset($this->headers[$name]);
        return $this;
    }

    public function getBasePath(): ?string
    {
        return $this->basePath;
    }

    public function setBasePath(?string $path): IMail
    {
        $this->basePath = $path;
        return $this;
    }

    public function extend(string $class): IMail
    {
        return $this->getExtensionFactory($class)->extend($this);
    }

    /**
     * @param class-string $class
     */
    public function getExtensionFactory(string $class): IExtension
    {
        $factory = $this->container->getByType($class, false);

        if ($factory === null) {
            throw new InvalidArgumentException("Mail extension '{$class}' is not registered as a service in DI container");
        }

        if (!($factory instanceof IExtension)) {
            throw new InvalidArgumentException("Mail extension '{$class}' does not implement " . IExtension::class);
        }

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

    protected function clearRecipients(string $type): IMail
    {
        unset($this->recipients[$type]);
        return $this;
    }

    protected function templatePath(string $type): string
    {
        return implode(DIRECTORY_SEPARATOR, [ $this->path, "{$this->template}.{$type}.latte" ]);
    }

    protected function renderSubject(): string
    {
        $path = $this->templatePath('html');

        if ($this->template === '' || !is_file($path)) {
            return '';
        }

        $this->latte->setContentType(ContentType::Html);

        if (!$this->latte->createTemplate($path, $this->data)->hasBlock(self::SUBJECT_BLOCK)) {
            return '';
        }

        $subject = $this->render($path, ContentType::Html, self::SUBJECT_BLOCK);
        $subject = html_entity_decode(strip_tags($subject), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('#\s+#u', ' ', $subject));
    }

    protected function render(string $path, string $contentType, ?string $block = null): string
    {
        $this->latte->setContentType($contentType);

        if (!$this->strict) {
            return $this->latte->renderToString($path, $this->data, $block);
        }

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            return $this->latte->renderToString($path, $this->data, $block);
        } finally {
            restore_error_handler();
        }
    }
}
