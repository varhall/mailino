<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Config;
use Varhall\Mailino\Extensions\Mjml\IMjmlRenderer;
use Varhall\Mailino\Extensions\Mjml\MjmlBinaryRenderer;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Renders MJML in HTML body using local MJML CLI (configuration section "mjml.binary")
 */
class MjmlBinary implements IExtension
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
            $config = $this->config->getExtension('mjml.binary');
            $this->renderer = new MjmlBinaryRenderer($config['binary'], $config['timeout'] ?? 30);
        }

        return $this->renderer;
    }
}
