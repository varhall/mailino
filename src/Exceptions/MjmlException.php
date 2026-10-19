<?php

declare(strict_types=1);

namespace Varhall\Mailino\Exceptions;

/**
 * MJML rendering failed (API error, process error, validation error, timeout)
 */
class MjmlException extends \RuntimeException
{
}
