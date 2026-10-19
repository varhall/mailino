<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Mail extension - transforms rendered mail (subject, bodies, recipients, headers).
 * Extensions must be registered as services, they are applied in order they were added to the mail.
 */
interface IExtension
{
    public function process(RenderedMail $mail): RenderedMail;
}
