<?php

declare(strict_types=1);

namespace Varhall\Mailino\Exchanges;

use Varhall\Mailino\Entities\Mail;

/**
 * Mail sending backend. Type your dependencies with this interface to be able to switch
 * implementation (SMTP, queue, log, null) in configuration.
 */
interface IExchange
{
    public function send(Mail $mail): void;
}
