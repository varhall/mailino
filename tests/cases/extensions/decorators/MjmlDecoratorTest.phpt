<?php

namespace Tests\Mailino\Extensions\Decorators;

use Qferrer\Mjml\Renderer\RendererInterface;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;


require_once __DIR__ . '/../../../bootstrap.php';

class MjmlDecoratorTest extends BaseTestCase
{
    public function testGetHtmlBody()
    {
        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getHtmlBody')->andReturn('html');

        $mjml = \Mockery::mock(RendererInterface::class);
        $mjml->shouldReceive('render')->with('html')->andReturn('mjml');

        $decorator = new MjmlDecorator($mail, $mjml);

        Assert::equal('mjml', $decorator->getHtmlBody());
    }
}

(new MjmlDecoratorTest())->run();
