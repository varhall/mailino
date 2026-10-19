<?php

declare(strict_types=1);

namespace Varhall\Mailino;

use Nette\Bridges\ApplicationLatte\LatteFactory;

/**
 * Creates Latte engine using application's Latte factory (nette/application), so mail templates
 * share configuration of application templates - translator, filters, extensions and cache.
 *
 * services:
 *     mailino.template_factory: Varhall\Mailino\NetteLatteEngineFactory
 */
class NetteLatteEngineFactory implements ILatteEngineFactory
{
    private LatteFactory $factory;

    public function __construct(LatteFactory $factory)
    {
        $this->factory = $factory;
    }

    public function createLatte(): \Latte\Engine
    {
        return $this->factory->create();
    }
}
