<?php

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Config;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Extensions\Decorators\PrefixDecorator;

class Prefix implements IExtension
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function extend(IMail $mail): IMail
    {
        return new PrefixDecorator($mail, $this->config->getExtension('prefix'));
    }
}