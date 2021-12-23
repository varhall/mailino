<?php

namespace Varhall\Mailino\Extensions\Decorators;

use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Extensions\IExtension;

abstract class MailDecorator implements IMail
{
    /** @var IMail */
    protected $mail;


    public function __construct(IMail $mail)
    {
        $this->mail = $mail;
    }

    public function getFrom(): Recipient
    {
        return $this->mail->getFrom();
    }

    public function setFrom(string $email, string $name = null): IMail
    {
        return $this->mail->setFrom($email, $name);
    }

    public function getSubject(): string
    {
        return $this->mail->getSubject();
    }

    public function setSubject(string $subject, string $prefix = null): IMail
    {
        return $this->mail->setSubject($subject, $prefix);
    }

    public function getData(): array
    {
        return $this->mail->getData();
    }

    public function setData(array $data): IMail
    {
        return $this->mail->setData($data);
    }

    public function getTemplate(): string
    {
        return $this->mail->getTemplate();
    }

    public function setTemplate(string $template): IMail
    {
        return $this->mail->setTemplate($template);
    }

    public function getHtmlBody(): string
    {
        return $this->mail->getHtmlBody();
    }

    public function getPlainBody(): string
    {
        return $this->mail->getPlainBody();
    }

    public function getTo(): array
    {
        return $this->mail->getTo();
    }

    public function addTo(string $email, string $name = null): IMail
    {
        return $this->mail->addTo($email, $name);
    }

    public function getCc(): array
    {
        return $this->mail->getCc();
    }

    public function addCc(string $email, string $name = null): IMail
    {
        return $this->mail->addCc($email);
    }

    public function getBcc(): array
    {
        return $this->mail->getBcc();
    }

    public function addBcc(string $email, string $name = null): IMail
    {
        return $this->mail->addBcc($email);
    }

    public function getFiles(): array
    {
        return $this->mail->getFiles();
    }

    public function addFile($file, string $name = null): IMail
    {
        return $this->mail->addFile($file, $name);
    }

    public function extend(string $class): IMail
    {
        return $this->getExtensionFactory($class)->extend($this);
    }

    public function getExtensionFactory(string $class): IExtension
    {
        return $this->mail->getExtensionFactory($class);
    }
}