<?php

namespace Tests\Mailino\Extensions;

use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\MjmlBinary;
use Varhall\Mailino\Extensions\Mjml\MjmlHttpApi;

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

    public function testApiUrlFromConfig()
    {
        $mail = \Mockery::mock(Mail::class);

        $decorator = $this->getContainer()->getByType(MjmlApi::class)->extend($mail);

        $renderer = (fn() => $this->mjml)->call($decorator);
        $api = (fn() => $this->client)->call($renderer);

        Assert::type(MjmlHttpApi::class, $api);
        Assert::equal('https://mjml.local/v1', $api->getUrl());
    }
}

(new MjmlTest())->run();
