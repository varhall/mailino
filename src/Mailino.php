<?php

namespace Varhall\Mailino;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Mail\Mailer;
use Qferrer\Mjml\Renderer\RendererInterface;
use Varhall\Mailino\Config\Config;

class Mailino
{
    /** @var Config */
    protected $config;

    /** @var Mailer */
    protected $mailer;

    /** @var Engine */
    protected $latte;

    /** @var RendererInterface */
    protected $renderer;


    public function __construct(Config $config, Mailer $mailer, Engine $latte, RendererInterface $renderer)
    {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->latte = $latte;
        $this->renderer = $renderer;
    }


    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getMailer(): Mailer
    {
        return $this->mailer;
    }

    public function getLatte(): Engine
    {
        return $this->latte;
    }

    public function getRenderer(): RendererInterface
    {
        return $this->renderer;
    }
}