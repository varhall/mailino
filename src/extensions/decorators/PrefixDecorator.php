<?php

namespace Varhall\Mailino\Extensions\Decorators;

use Varhall\Mailino\Entities\IMail;

class PrefixDecorator extends MailDecorator
{
    /** @var array */
    protected $config;

    public function __construct(IMail $mail, array $config = [])
    {
        parent::__construct($mail);

        $this->config = $config;
    }

    public function getSubject(): string
    {
        $subject = parent::getSubject();

        if (!empty($this->getPrefix()))
            $subject = "[{$this->getPrefix()}] {$subject}";

        return $subject;
    }

    public function getPrefix(): string
    {
        return array_key_exists('subject', $this->config) ? $this->config['subject'] : '';
    }
}