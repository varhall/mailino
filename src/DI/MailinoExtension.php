<?php

declare(strict_types=1);

namespace Varhall\Mailino\DI;

use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Varhall\Mailino\Config;
use Varhall\Mailino\Exchanges\MailExchange;
use Varhall\Mailino\Exchanges\MessageFactory;
use Varhall\Mailino\Extensions\ExtensionRegistry;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\Extensions\Mjml\MjmlApiRenderer;
use Varhall\Mailino\LatteEngineFactory;
use Varhall\Mailino\Queue\MailSerializer;
use Varhall\Mailino\Rendering\MailRenderer;

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

    public function loadConfiguration(): void
    {
        $this->compiler->loadDefinitionsFromConfig(
            $this->loadFromFile(__DIR__ . '/mailino.neon')['services'],
        );

        $builder = $this->getContainerBuilder();

        /** @var \stdClass $config */
        $config = $this->config;

        $builder->addDefinition($this->prefix('config'))
                ->setFactory(Config::class, [ $config ]);

        $builder->addDefinition($this->prefix('registry'))
                ->setFactory(ExtensionRegistry::class);

        $builder->addDefinition($this->prefix('renderer'))
                ->setFactory(MailRenderer::class, [
                    'templateDir'   => $config->template_dir,
                    'strict'        => $config->strict,
                ]);

        $builder->addDefinition($this->prefix('message_factory'))
                ->setFactory(MessageFactory::class);

        $builder->addDefinition($this->prefix('serializer'))
                ->setFactory(MailSerializer::class);

        // application may replace these services in its own configuration
        if (!$builder->hasDefinition($this->prefix('template_factory'))) {
            $builder->addDefinition($this->prefix('template_factory'))
                    ->setFactory(LatteEngineFactory::class, [ $config->temp_dir ]);
        }

        if (!$builder->hasDefinition($this->prefix('exchange'))) {
            $builder->addDefinition($this->prefix('exchange'))
                    ->setFactory(MailExchange::class);
        }
    }

    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();

        // register all extension services (built-in and application ones) into registry
        $registry = $builder->getDefinition($this->prefix('registry'));

        if ($registry instanceof ServiceDefinition) {
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
                'validation_level'  => Expect::anyOf(MjmlApiRenderer::VALIDATION_SOFT, MjmlApiRenderer::VALIDATION_STRICT)
                    ->default(MjmlApiRenderer::VALIDATION_SOFT),
            ]),
            'mjml.binary'   => $section([
                'binary'            => Expect::anyOf(Expect::string(), Expect::listOf('string'))->required(),
                'timeout'           => Expect::int(30)->min(0),
            ]),
        ])->otherItems(Expect::array())->skipDefaults()->castTo('array');
    }
}
