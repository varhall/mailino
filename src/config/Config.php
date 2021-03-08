<?php

namespace Varhall\Mailino\Config;

use Varhall\Mailino\Config\MjmlConfig;

class Config
{
    /** @var string */
    protected $templateDir;

    /** @var Sender */
    protected $sender;

    /** @var string */
    protected $subjectPrefix;

    /** @var bool */
    protected $verifySSL;


    public function __construct(string $templateDir, Sender $sender, string $subjectPrefix, bool $verifySSL)
    {
        $this->templateDir = $templateDir;
        $this->sender = $sender;
        $this->subjectPrefix = $subjectPrefix;
        $this->verifySSL = $verifySSL;
    }


    /// GETTERS

    public function getTemplateDir(): string
    {
        return $this->templateDir;
    }

    public function getSender(): Sender
    {
        return $this->sender;
    }

    public function getSubjectPrefix(): string
    {
        return $this->subjectPrefix;
    }

    public function isVerifySSL(): bool
    {
        return $this->verifySSL;
    }
}