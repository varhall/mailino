<?php

namespace Varhall\Mailino\Entities;

use Nette\Http\FileUpload;

class Attachment
{
    /** @var string */
    private $name;

    /** @var string */
    private $content;

    /** @var string */
    private $contentType;


    public function __construct(string $content, string $contentType, string $name = null)
    {
        $this->content = $content;
        $this->contentType = $contentType;
        $this->name = $name;
    }


    /// FACTORY METHODS

    public static function fromUpload(FileUpload $file, string $name = null): self
    {
        return new static($file->getContents(), $file->getContentType(), $name ?? $file->getUntrustedName());
    }

    public static function fromPath(string $path, string $name = null): self
    {
        return self::fromFile(new \SplFileInfo($path), $name);
    }

    public static function fromFile(\SplFileInfo $file, string $name = null): self
    {
        $path = $file->getPathname();

        if (preg_match('#^http#i', $file->getPathname())) {
            $path = tempnam(sys_get_temp_dir(), '');
            file_put_contents($path, file_get_contents($file->getPathname()));
        }

        if ($path !== $file->getPathname())
            unlink($path);

        return new static(file_get_contents($path), mime_content_type($path), $name ?? $file->getFilename());
    }


    /// GETTERS & SETTERS

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
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
}