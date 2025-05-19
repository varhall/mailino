<?php

namespace Varhall\Mailino\Extensions;

use Qferrer\Mjml\Http\CurlApi;
use Qferrer\Mjml\Renderer\ApiRenderer;
use Varhall\Mailino\Config;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;

class MjmlApi implements IExtension
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function extend(IMail $mail): IMail
    {
        $config = $this->config->getExtension('mjml.api');
        $api = new CurlApi($config['id'], $config['secret']);
        $mjml = new ApiRenderer($api);

        return new MjmlDecorator($mail, $mjml);
    }
}