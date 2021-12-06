<?php

namespace Varhall\Mailino\Extensions;

use Varhall\Mailino\Entities\IMail;

interface IExtension
{
    public function extend(IMail $mail): IMail;
}