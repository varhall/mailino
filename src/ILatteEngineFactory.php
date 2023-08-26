<?php

namespace Varhall\Mailino;

interface ILatteEngineFactory
{
    public function createLatte(): \Latte\Engine;
}