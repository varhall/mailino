<?php

namespace Tests\Mailino\Extensions;

use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\Decorators\PrefixDecorator;
use Varhall\Mailino\Extensions\Prefix;


require_once __DIR__ . '/../../bootstrap.php';

class PrefixTest extends ContainerTestCase
{
    public function testExtend()
    {
        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getSubject')->andReturn('subject');

        $factory = $this->getContainer()->getByType(Prefix::class);
        $decorator = $factory->extend($mail);

        Assert::type(PrefixDecorator::class, $decorator);
        Assert::equal('[TST] subject', $decorator->getSubject());
    }
}

(new PrefixTest())->run();
