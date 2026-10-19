<?php

namespace Varhall\Mailino\Extensions\Decorators;

use Varhall\Mailino\Entities\IMail;

class PrefixDecorator extends MailDecorator
{
    protected array $config;

    public function __construct(IMail $mail, array $config = [])
    {
        parent::__construct($mail);

        $this->config = $config;
    }

    public function getSubject(): string
    {
        $subject = parent::getSubject();

        $prefix = "[{$this->getPrefix()}] ";

        // idempotent - extension applied multiple times adds prefix only once
        if ($this->getPrefix() !== '' && !str_starts_with($subject, $prefix)) {
            $subject = $prefix . $subject;
        }

        return $subject;
    }

    public function getPrefix(): string
    {
        return (string) ($this->config['subject'] ?? '');
    }
}
