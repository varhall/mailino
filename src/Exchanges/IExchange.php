<?php

namespace Varhall\Mailino\Exchanges;

use Varhall\Mailino\Entities\IMail;

/**
 * Mail sending backend. Type your dependencies with this interface to be able to switch
 * implementation (SMTP, log, null) in configuration.
 */
interface IExchange
{
    public function send(IMail $mail): void;
}
