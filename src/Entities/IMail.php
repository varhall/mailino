<?php

namespace Varhall\Mailino\Entities;

use Varhall\Mailino\Extensions\IExtension;

interface IMail
{
    public function getFrom(): Recipient;

    public function setFrom(string $email, ?string $name = null): IMail;

    /**
     * Returns explicitly set subject, otherwise subject rendered from {define subject} block of HTML template
     */
    public function getSubject(): string;

    public function setSubject(string $subject): IMail;

    public function getData(): array;

    public function setData(array $data): IMail;

    public function getTemplate(): string;

    public function setTemplate(string $template): IMail;

    /**
     * @deprecated mail is rendered by renderer in 2.0, use Mailino::preview() to get rendered content
     */
    public function getHtmlBody(): string;

    /**
     * Returns empty string when plain template does not exist (plain text is generated from HTML when sending)
     *
     * @deprecated mail is rendered by renderer in 2.0, use Mailino::preview() to get rendered content
     */
    public function getPlainBody(): string;

    public function getTo(): array;

    public function addTo(string $email, ?string $name = null): IMail;

    public function clearTo(): IMail;

    public function getCc(): array;

    public function addCc(string $email, ?string $name = null): IMail;

    public function clearCc(): IMail;

    public function getBcc(): array;

    public function addBcc(string $email, ?string $name = null): IMail;

    public function clearBcc(): IMail;

    public function getFiles(): array;

    /**
     * @param string|\SplFileInfo|\Nette\Http\FileUpload|Attachment $file
     */
    public function addFile($file, ?string $name = null): IMail;

    public function getReplyTo(): array;

    public function addReplyTo(string $email, ?string $name = null): IMail;

    public function clearReplyTo(): IMail;

    public function getReturnPath(): ?string;

    public function setReturnPath(string $email): IMail;

    public function getPriority(): ?int;

    public function setPriority(int $priority): IMail;

    public function getHeaders(): array;

    public function getHeader(string $name): ?string;

    public function addHeader(string $name, string $value): IMail;

    public function removeHeader(string $name): IMail;

    /**
     * Directory used to embed local images referenced in HTML body as inline attachments
     */
    public function getBasePath(): ?string;

    public function setBasePath(?string $path): IMail;

    public function extend(string $class): IMail;

    public function getExtensionFactory(string $class): IExtension;
}
