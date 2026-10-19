<?php

namespace Varhall\Mailino\DI;

use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Varhall\Mailino\Config;
use Varhall\Mailino\Exchanges\MailExchange;
use Varhall\Mailino\Extensions\ExtensionRegistry;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\Extensions\Mjml\MjmlHttpApi;
use Varhall\Mailino\LatteEngineFactory;

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
            'priority'      => Expect::int()->min(1)->max(5)->nullable()->default(null),
            'headers'       => Expect::arrayOf('string', 'string')->default([]),
            'embed_dir'     => Expect::string()->nullable()->default(null),
            'temp_dir'      => Expect::string()->nullable()->default(null),
            'strict'        => Expect::bool(false),
            'default_extensions' => Expect::listOf('string')->default([]),
            'extensions'    => $this->getExtensionsSchema(),
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

        // application may replace these services in its own configuration
        if (!$builder->hasDefinition($this->prefix('template_factory'))) {
            $builder->addDefinition($this->prefix('template_factory'))
                    ->setFactory(LatteEngineFactory::class);
        }

        if (!$builder->hasDefinition($this->prefix('exchange'))) {
            $builder->addDefinition($this->prefix('exchange'))
                    ->setFactory(MailExchange::class);
        }

        $builder->addDefinition($this->prefix('registry'))
                ->setFactory(ExtensionRegistry::class);
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        $definition = $builder->getDefinition($this->prefix('template_factory'));

        /** @var \stdClass $config */
        $config = $this->config;

        // configure default factory only, application may replace the service with own implementation
        if ($definition instanceof \Nette\DI\Definitions\ServiceDefinition && $definition->getType() === LatteEngineFactory::class) {
            $definition->setArguments([ $config->temp_dir ]);
        }

        // register all extension services (built-in and application ones) into registry
        $registry = $builder->getDefinition($this->prefix('registry'));

        if ($registry instanceof \Nette\DI\Definitions\ServiceDefinition) {
            foreach (array_keys($builder->findByType(IExtension::class)) as $name) {
                $registry->addSetup('add', [ '@' . $name ]);
            }
        }
    }

    protected function getExtensionsSchema(): Schema
    {
        $section = fn(array $items) => Expect::anyOf(null, Expect::structure($items)->castTo('array'))->default(null);

        return Expect::structure([
            'prefix'        => $section([
                'subject'           => Expect::string()->required(),
            ]),
            'mjml.api'      => $section([
                'id'                => Expect::string()->required(),
                'secret'            => Expect::string()->required(),
                'url'               => Expect::string()->nullable()->default(null),
                'timeout'           => Expect::int(30)->min(0),
                'connect_timeout'   => Expect::int(10)->min(0),
                'validation_level'  => Expect::anyOf(MjmlHttpApi::VALIDATION_SOFT, MjmlHttpApi::VALIDATION_STRICT)->default(MjmlHttpApi::VALIDATION_SOFT),
            ]),
            'mjml.binary'   => $section([
                'binary'            => Expect::anyOf(Expect::string(), Expect::listOf('string'))->required(),
                'timeout'           => Expect::int(30)->min(0),
            ]),
        ])->otherItems(Expect::array())->skipDefaults()->castTo('array');
    }
}
