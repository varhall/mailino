<?php

namespace Varhall\Mailino\DI;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Varhall\Mailino\Config;

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
            'extensions'        => Expect::array()
        ]);
    }

    public function loadConfiguration()
    {
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__ . '/mailino.neon')['services'],
        );

        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('config'))
                ->setFactory(Config::class, [ $this->config ]);
    }
}
