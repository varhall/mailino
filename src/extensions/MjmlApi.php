<?php

namespace Varhall\Mailino\Extensions;

use Qferrer\Mjml\Renderer\ApiRenderer;
use Varhall\Mailino\Config;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;
use Varhall\Mailino\Extensions\Mjml\MjmlHttpApi;

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
        $api = new MjmlHttpApi(
            $config['id'],
            $config['secret'],
            $config['url'] ?? null,
            null,
            $config['timeout'] ?? 30,
            $config['connect_timeout'] ?? 10,
            $config['validation_level'] ?? MjmlHttpApi::VALIDATION_SOFT
        );
        $mjml = new ApiRenderer($api);

        return new MjmlDecorator($mail, $mjml);
    }
}
