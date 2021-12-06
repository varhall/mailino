<?php

namespace Varhall\Mailino\Exchanges;

use Nette\SmartObject;
use Varhall\Mailino\Entities\IMail;

abstract class Exchange
{
    use SmartObject;

    public $onSending   = [];
    public $onSuccess   = [];
    public $onError     = [];

    public abstract function send(IMail $mail): void;
}