<?php

namespace Varhall\Mailino;

use Nette\DI\Container;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Entities\Mail;

class Mailino
{
    protected Container $container;

    protected Config $config;

    protected ILatteEngineFactory $latteFactory;

    public function __construct(Config $config, ILatteEngineFactory $latteFactory, Container $container)
    {
        $this->config = $config;
        $this->latteFactory = $latteFactory;
        $this->container = $container;
    }

    public function create(string $template, array $data = []): IMail
    {
        $from = $this->config->getValue('from');

        $mail = (new Mail($this->latteFactory->createLatte(), $this->container, $this->config->getValue('template_dir')))
            ->setTemplate($template)
            ->setData($data)
            ->setFrom($from->email, $from->name);

        $replyTo = $this->config->getValue('reply_to');
        if ($replyTo !== null) {
            $mail->addReplyTo($replyTo->email, $replyTo->name);
        }

        $returnPath = $this->config->getValue('return_path');
        if ($returnPath !== null) {
            $mail->setReturnPath($returnPath);
        }

        $priority = $this->config->getValue('priority');
        if ($priority !== null) {
            $mail->setPriority($priority);
        }

        foreach ($this->config->getValue('headers') as $name => $value) {
            $mail->addHeader($name, $value);
        }

        return $mail;
    }
}
