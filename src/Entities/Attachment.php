<?php

declare(strict_types=1);

namespace Varhall\Mailino\Entities;

use Nette\FileNotFoundException;
use Nette\Http\FileUpload;
use Nette\InvalidArgumentException;
use Nette\IOException;

/**
 * Immutable mail attachment
 */
final class Attachment
{
    private const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    public function __construct(
        private readonly string $content,
        private readonly string $contentType,
        private readonly string $name
    ) {
    }


    /// FACTORY METHODS

    public static function fromUpload(FileUpload $file, ?string $name = null): self
    {
        return new self(
            $file->getContents() ?? '',
            $file->getContentType() ?? self::DEFAULT_CONTENT_TYPE,
            $name ?? $file->getUntrustedName()
        );
    }

    /**
     * Creates attachment from content in memory (e.g. generated PDF). Content type is detected when not given.
     */
    public static function fromContent(string $content, string $name, ?string $contentType = null): self
    {
        return new self($content, $contentType ?? self::detectContentType($content), $name);
    }

    /**
     * Reads local file. Never pass user input as path.
     */
    public static function fromPath(string $path, ?string $name = null): self
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            throw new InvalidArgumentException("Attachment path '{$path}' must be local file, use Attachment::fromUrl() for URLs");
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new FileNotFoundException("Attachment file '{$path}' does not exist or is not readable");
        }

        $content = @file_get_contents($path);

        if ($content === false) {
            throw new IOException("Unable to read attachment file '{$path}'");
        }

        return new self($content, self::detectContentType($content), $name ?? basename($path));
    }

    public static function fromFile(\SplFileInfo $file, ?string $name = null): self
    {
        return self::fromPath($file->getPathname(), $name ?? $file->getFilename());
    }

    /**
     * Downloads attachment from URL. Never pass user input as URL (SSRF).
     */
    public static function fromUrl(string $url, ?string $name = null): self
    {
        if (!preg_match('#^https?://#i', $url)) {
            throw new InvalidArgumentException("Attachment URL '{$url}' must use http or https scheme");
        }

        $content = @file_get_contents($url);

        if ($content === false) {
            throw new IOException("Unable to download attachment from '{$url}'");
        }

        $name ??= basename((string) parse_url($url, PHP_URL_PATH)) ?: 'attachment';

        return new self($content, self::detectContentType($content), $name);
    }


    /// GETTERS

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function withName(string $name): self
    {
        return new self($this->content, $this->contentType, $name);
    }


    /// PRIVATE METHODS

    private static function detectContentType(string $content): string
    {
        if (!class_exists(\finfo::class)) {
            return self::DEFAULT_CONTENT_TYPE;
        }

        return (new \finfo(FILEINFO_MIME_TYPE))->buffer($content) ?: self::DEFAULT_CONTENT_TYPE;
    }
}
