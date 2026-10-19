<?php

namespace Varhall\Mailino\Entities;

use Nette\FileNotFoundException;
use Nette\Http\FileUpload;
use Nette\IOException;

/**
 * @phpstan-consistent-constructor
 */
class Attachment
{
    private const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    private ?string $name;

    private string $content;

    private string $contentType;


    public function __construct(string $content, string $contentType, ?string $name = null)
    {
        $this->content = $content;
        $this->contentType = $contentType;
        $this->name = $name;
    }


    /// FACTORY METHODS

    public static function fromUpload(FileUpload $file, ?string $name = null): self
    {
        return new static($file->getContents(), $file->getContentType() ?? self::DEFAULT_CONTENT_TYPE, $name ?? $file->getUntrustedName());
    }

    /**
     * Creates attachment from content in memory (e.g. generated PDF). Content type is detected when not given.
     */
    public static function fromContent(string $content, string $name, ?string $contentType = null): self
    {
        return new static($content, $contentType ?? self::detectContentType($content), $name);
    }

    public static function fromPath(string $path, ?string $name = null): self
    {
        return self::fromFile(new \SplFileInfo($path), $name);
    }

    public static function fromFile(\SplFileInfo $file, ?string $name = null): self
    {
        if (self::isUrl($file->getPathname())) {
            trigger_error('Creating attachment from URL using ' . __METHOD__ . '() is deprecated, use ' . self::class . '::fromUrl()', E_USER_DEPRECATED);
            return self::fromUrl($file->getPathname(), $name);
        }

        $path = $file->getPathname();

        if (!is_file($path) || !is_readable($path)) {
            throw new FileNotFoundException("Attachment file '{$path}' does not exist or is not readable");
        }

        $content = @file_get_contents($path);

        if ($content === false) {
            throw new IOException("Unable to read attachment file '{$path}'");
        }

        return new static($content, self::detectContentType($content), $name ?? $file->getFilename());
    }

    /**
     * Downloads attachment from URL. Never pass user input as URL (SSRF).
     */
    public static function fromUrl(string $url, ?string $name = null): self
    {
        if (!self::isUrl($url)) {
            throw new \Nette\InvalidArgumentException("Attachment URL '{$url}' must use http or https scheme");
        }

        $content = @file_get_contents($url);

        if ($content === false) {
            throw new IOException("Unable to download attachment from '{$url}'");
        }

        $name ??= basename((string) parse_url($url, PHP_URL_PATH)) ?: 'attachment';

        return new static($content, self::detectContentType($content), $name);
    }


    /// GETTERS & SETTERS

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }


    /// PRIVATE METHODS

    private static function isUrl(string $path): bool
    {
        return (bool) preg_match('#^https?://#i', $path);
    }

    private static function detectContentType(string $content): string
    {
        if (!class_exists(\finfo::class)) {
            return self::DEFAULT_CONTENT_TYPE;
        }

        return (new \finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: self::DEFAULT_CONTENT_TYPE;
    }
}
