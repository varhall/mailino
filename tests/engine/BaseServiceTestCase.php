<?php

namespace Tests\Engine;

use Varhall\Mailino\Config\Config;
use Varhall\Mailino\Config\Sender;

abstract class BaseServiceTestCase extends BaseTestCase
{
    protected function getConfig(): Config
    {
        return new Config('directory', new Sender('sender@test.com', 'Sender'), 'TST', false);
    }
}