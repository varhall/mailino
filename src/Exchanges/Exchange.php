<?php

namespace Varhall\Mailino\Exchanges;

use Nette\SmartObject;
use Varhall\Mailino\Entities\IMail;

/**
 * @method void onSending(IMail $mail)
 * @method void onSuccess(IMail $mail)
 * @method void onError(IMail $mail, \Throwable $exception)
 */
abstract class Exchange implements IExchange
{
    use SmartObject;

    /** @var array<callable(IMail): void> */
    public $onSending   = [];

    /** @var array<callable(IMail): void> */
    public $onSuccess   = [];

    /** @var array<callable(IMail, \Throwable): void> */
    public $onError     = [];

    abstract public function send(IMail $mail): void;

    /**
     * Runs delivery and triggers events, exception is re-thrown after onError handlers
     */
    protected function dispatch(IMail $mail, callable $delivery): void
    {
        $this->onSending($mail);

        try {
            $delivery();
            $this->onSuccess($mail);
        } catch (\Throwable $ex) {
            $this->onError($mail, $ex);
            throw $ex;
        }
    }
}
