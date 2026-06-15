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
            'template_dir'  => Expect::string()->required(),
            'from'          => Expect::structure([
                'email'         => Expect::email()->required(),
                'name'          => Expect::string()->nullable()->default(null),
            ])->required(),
            'reply_to'      => Expect::anyOf(
                null,
                Expect::structure([
                    'email'         => Expect::email()->required(),
                    'name'          => Expect::string()->nullable()->default(null),
                ])->castTo('object')
            )->default(null),
            'return_path'   => Expect::email()->nullable()->default(null),
            'priority'      => Expect::int()->nullable()->default(null),
            'headers'       => Expect::array()->default([]),
            'extensions'    => Expect::array()->default([]),
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
