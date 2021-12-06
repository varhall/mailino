<?php

namespace Varhall\Mailino\Extensions;

use Qferrer\Mjml\Renderer\ApiRenderer;
use Varhall\Mailino\Config;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;

class MjmlApi implements IExtension
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function extend(IMail $mail): IMail
    {
        $config = $this->config->getExtension('mjml.api');
        $mjml = new ApiRenderer($config['id'], $config['secret']);

        return new MjmlDecorator($mail, $mjml);
    }
}