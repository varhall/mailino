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
        $this->mail->setFrom($email, $name);
        return $this;
    }

    public function getSubject(): string
    {
        return $this->mail->getSubject();
    }

    public function setSubject(string $subject, string $prefix = null): IMail
    {
        $this->mail->setSubject($subject, $prefix);
        return $this;
    }

    public function getData(): array
    {
        return $this->mail->getData();
    }

    public function setData(array $data): IMail
    {
        $this->mail->setData($data);
        return $this;
    }

    public function getTemplate(): string
    {
        return $this->mail->getTemplate();
    }

    public function setTemplate(string $template): IMail
    {
        $this->mail->setTemplate($template);
        return $this;
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
        $this->mail->addTo($email, $name);
        return $this;
    }

    public function getCc(): array
    {
        return $this->mail->getCc();
    }

    public function addCc(string $email, string $name = null): IMail
    {
        $this->mail->addCc($email);
        return $this;
    }

    public function getBcc(): array
    {
        return $this->mail->getBcc();
    }

    public function addBcc(string $email, string $name = null): IMail
    {
        $this->mail->addBcc($email);
        return $this;
    }

    public function getFiles(): array
    {
        return $this->mail->getFiles();
    }

    public function addFile($file, string $name = null): IMail
    {
        $this->mail->addFile($file, $name);
        return $this;
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