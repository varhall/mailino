<?php

namespace Varhall\Mailino\Extensions;

use Qferrer\Mjml\Renderer\BinaryRenderer;
use Varhall\Mailino\Config;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;

class MjmlBinary implements IExtension
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function extend(IMail $mail): IMail
    {
        $config = $this->config->getExtension('mjml.binary');
        $mjml = new BinaryRenderer($config['binary']);

        return new MjmlDecorator($mail, $mjml);
    }
}