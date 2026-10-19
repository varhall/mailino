<?php

declare(strict_types=1);

namespace Varhall\Mailino\Exchanges;

use Nette\SmartObject;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Rendering\MailRenderer;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Base exchange - renders mail, delivers it and triggers events
 *
 * @method void onSending(Mail $mail)
 * @method void onSuccess(Mail $mail, RenderedMail $rendered)
 * @method void onError(Mail $mail, \Throwable $exception)
 */
abstract class Exchange implements IExchange
{
    use SmartObject;

    /** @var array<callable(Mail): void> */
    public array $onSending = [];

    /** @var array<callable(Mail, RenderedMail): void> */
    public array $onSuccess = [];

    /** @var array<callable(Mail, \Throwable): void> */
    public array $onError = [];

    public function __construct(
        protected readonly MailRenderer $renderer
    ) {
    }

    public function send(Mail $mail): void
    {
        $this->onSending($mail);

        try {
            $rendered = $this->renderer->render($mail);
            $this->deliver($rendered);
            $this->onSuccess($mail, $rendered);
        } catch (\Throwable $ex) {
            $this->onError($mail, $ex);
            throw $ex;
        }
    }

    abstract protected function deliver(RenderedMail $mail): void;
}
