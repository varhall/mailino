<?php

namespace Varhall\Mailino;

class LatteEngineFactory implements ILatteEngineFactory
{
    public function createLatte(): \Latte\Engine
    {
        return new \Latte\Engine();
    }
}