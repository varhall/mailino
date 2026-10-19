<?php

namespace Varhall\Mailino\Exchanges;

use Nette\SmartObject;
use Varhall\Mailino\Entities\IMail;

/**
 * @method void onSending(IMail $mail)
 * @method void onSuccess(IMail $mail)
 * @method void onError(IMail $mail, \Throwable $exception)
 */
abstract class Exchange
{
    use SmartObject;

    /** @var array<callable(IMail): void> */
    public $onSending   = [];

    /** @var array<callable(IMail): void> */
    public $onSuccess   = [];

    /** @var array<callable(IMail, \Throwable): void> */
    public $onError     = [];

    abstract public function send(IMail $mail): void;
}
