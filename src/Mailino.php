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

    /** @var ILatteEngineFactory */
    protected $latteFactory;

    public function __construct(Config $config, ILatteEngineFactory $latteFactory, Container $container)
    {
        $this->config = $config;
        $this->latteFactory = $latteFactory;
        $this->container = $container;
    }

    public function create(string $template, array $data = []): IMail
    {
        $dir = $this->config->getValue('template_dir');
        $sender = $this->config->getValue('sender');

        $mail = (new Mail($this->latteFactory->createLatte(), $this->container, $dir))
            ->setTemplate($template)
            ->setData($data)
            ->setFrom($sender->email, $sender->name);

        return $mail;
    }
}
