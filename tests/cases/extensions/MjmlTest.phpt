<?php

namespace Tests\Mailino\Extensions;

use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\MjmlBinary;

require_once __DIR__ . '/../../bootstrap.php';

class MjmlTest extends ContainerTestCase
{
    public function getTypes()
    {
        return [ [MjmlBinary::class], [MjmlApi::class] ];
    }

    /** @dataProvider getTypes */
    public function testExtend($type)
    {
        $mail = \Mockery::mock(Mail::class);

        $factory = $this->getContainer()->getByType($type);
        $decorator = $factory->extend($mail);

        Assert::type(MjmlDecorator::class, $decorator);
    }
}

(new MjmlTest())->run();
