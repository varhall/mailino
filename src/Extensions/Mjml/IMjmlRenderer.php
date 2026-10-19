<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions\Mjml;

use Varhall\Mailino\Exceptions\MjmlException;

interface IMjmlRenderer
{
    /**
     * @throws MjmlException
     */
    public function render(string $mjml): string;
}
