<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Config;
use Varhall\Mailino\Extensions\Mjml\IMjmlRenderer;
use Varhall\Mailino\Extensions\Mjml\MjmlApiRenderer;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Renders MJML in HTML body using MJML HTTP API (configuration section "mjml.api")
 */
class MjmlApi implements IExtension
{
    private ?IMjmlRenderer $renderer = null;

    public function __construct(
        private readonly Config $config
    ) {
    }

    public function process(RenderedMail $mail): RenderedMail
    {
        return $mail->withHtmlBody($this->getRenderer()->render($mail->getHtmlBody()));
    }

    public function getRenderer(): IMjmlRenderer
    {
        if ($this->renderer === null) {
            $config = $this->config->getExtension('mjml.api');

            $this->renderer = new MjmlApiRenderer(
                $config['id'],
                $config['secret'],
                $config['url'] ?? null,
                $config['timeout'] ?? 30,
                $config['connect_timeout'] ?? 10,
                $config['validation_level'] ?? MjmlApiRenderer::VALIDATION_SOFT
            );
        }

        return $this->renderer;
    }
}
