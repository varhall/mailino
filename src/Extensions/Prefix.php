<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Config;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Adds prefix to subject - "[PREFIX] subject" (configuration section "prefix")
 */
class Prefix implements IExtension
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function process(RenderedMail $mail): RenderedMail
    {
        $prefix = (string) ($this->config->getExtension('prefix')['subject'] ?? '');

        if ($prefix === '' || str_starts_with($mail->getSubject(), "[{$prefix}] ")) {
            return $mail;
        }

        return $mail->withSubject(trim("[{$prefix}] {$mail->getSubject()}"));
    }
}
