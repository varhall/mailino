<?php

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Config;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;
use Varhall\Mailino\Extensions\Mjml\MjmlBinaryRenderer;

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
        $mjml = new MjmlBinaryRenderer($config['binary'], $config['timeout'] ?? 30);

        return new MjmlDecorator($mail, $mjml);
    }
}
