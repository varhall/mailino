<?php

namespace Tests\Engine;

use Varhall\Mailino\Extensions\ExtensionRegistry;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\LatteEngineFactory;
use Varhall\Mailino\Rendering\MailRenderer;

class Helpers
{
    /**
     * @param IExtension[] $extensions
     */
    public static function renderer(array $extensions = [], bool $strict = false): MailRenderer
    {
        return new MailRenderer(FIXTURES_DIR . '/templates', new LatteEngineFactory(), new ExtensionRegistry($extensions), $strict);
    }
}
