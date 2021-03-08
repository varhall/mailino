<?php

namespace Varhall\Mailino\DI;

use Latte\Engine;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Qferrer\Mjml\Renderer\ApiRenderer;
use Varhall\Mailino\Config\Config;
use Varhall\Mailino\Config\Sender;
use Varhall\Mailino\Mailino;
use Varhall\Mailino\Services\HtmlMailService;
use Varhall\Mailino\Services\MjmlMailService;

/**
 * Nette extension class
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class MailinoExtension extends \Nette\DI\CompilerExtension
{
    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'template_dir'      => Expect::string()->required(),
            'sender'            => Expect::structure([
                'email'             => Expect::email()->required(),
                'name'              => Expect::string()->required()
            ]),
            'subject_prefix'    => Expect::string(),
            'verify_ssl'        => Expect::bool()->default(false),
            'mjml'              => Expect::structure([
                'api_id'            => Expect::string(),
                'secret'            => Expect::string()
            ])
        ]);
    }

    public function loadConfiguration()
    {
        $config = $this->config;
        $builder = $this->getContainerBuilder();

        $sender = $builder->addDefinition($this->prefix('sender'))->setFactory(Sender::class, [ $config->sender->email, $config->sender->name ]);

        $builder->addDefinition($this->prefix('config'))->setFactory(Config::class, [
            $config->template_dir,
            $sender,
            $config->subject_prefix,
            $config->verify_ssl
        ]);

        $builder->addDefinition($this->prefix('service.mjml'))->setFactory(ApiRenderer::class, [ $config->mjml->api_id ?? '', $config->mjml->secret ?? '' ]);
        $builder->addDefinition($this->prefix('service.latte'))->setType(Engine::class);

        $builder->addDefinition($this->prefix('mailino'))->setType(Mailino::class);

        $builder->addDefinition($this->prefix('mail.html'))->setType(HtmlMailService::class);
        $builder->addDefinition($this->prefix('mail.mjml'))->setType(MjmlMailService::class);
    }
}
