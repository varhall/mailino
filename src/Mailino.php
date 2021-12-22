<?php

namespace Varhall\Mailino;

use Nette\DI\Container;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\MailDecorator;

class Mailino
{
    /** @var Container */
    protected $container;

    /** @var Config */
    protected $config;

    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function create(string $template, array $data = []): IMail
    {
        $dir = $this->config->getValue('template_dir');
        $sender = $this->config->getValue('sender');

        $mail = (new Mail($this->container, $dir))
            ->setTemplate($template)
            ->setData($data)
            ->setFrom($sender->email, $sender->name);

        return $mail;
    }
}
