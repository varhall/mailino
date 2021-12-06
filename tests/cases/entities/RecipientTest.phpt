<?php

namespace Tests\Mailino\Entities;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Entities\Recipient;


require_once __DIR__ . '/../../bootstrap.php';

class RecipientTest extends BaseTestCase
{
    public function testGetters()
    {
        $recipient = new Recipient('pepa@novak.cz', 'pepa novak');

        Assert::equal('pepa@novak.cz', $recipient->getEmail());
        Assert::equal('pepa novak', $recipient->getName());
    }
}

(new RecipientTest())->run();
