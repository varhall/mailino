<?php

namespace Varhall\Mailino\Models;

use Varhall\Dbino\Model;
use Varhall\Dbino\Plugins\JsonPlugin;
use Varhall\Dbino\Plugins\TimestampPlugin;

class Emails extends Model
{
    protected function plugins()
    {
        return [
            new TimestampPlugin(),
            new JsonPlugin(['raw_data'])
        ];
    }

    protected function table()
    {
        return 'emails';
    }
}