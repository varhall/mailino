<?php

namespace Varhall\Mailino\Models;

use Varhall\Dbino\Model;
use Varhall\Dbino\Plugins\JsonPlugin;
use Varhall\Dbino\Plugins\TimestampPlugin;

/**
 * Model class of email. Used a bit as an email log.
 *
 * @author Ondrej Sibrava <sibrava@varhall.cz>
 */
class Email extends Model
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