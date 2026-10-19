<?php

declare(strict_types=1);

namespace Varhall\Mailino;

interface ILatteEngineFactory
{
    public function createLatte(): \Latte\Engine;
}
