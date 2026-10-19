<?php

namespace Tests\Mailino\Extensions\Decorators;

use Qferrer\Mjml\Exception\ProcessException;
use Qferrer\Mjml\Renderer\BinaryRenderer;
use Qferrer\Mjml\RendererInterface;
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

    public function testGetHtmlBody_Memoized()
    {
        $source = 'first';

        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getHtmlBody')->andReturnUsing(function () use (&$source) {
            return $source;
        });

        $mjml = \Mockery::mock(RendererInterface::class);
        $mjml->shouldReceive('render')->with('first')->once()->andReturn('html first');
        $mjml->shouldReceive('render')->with('second')->once()->andReturn('html second');

        $decorator = new MjmlDecorator($mail, $mjml);

        Assert::equal('html first', $decorator->getHtmlBody());
        Assert::equal('html first', $decorator->getHtmlBody());

        $source = 'second';
        Assert::equal('html second', $decorator->getHtmlBody());
        Assert::equal('html second', $decorator->getHtmlBody());
    }

    public function testBinaryRenderer()
    {
        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getHtmlBody')->andReturn('<mjml><mj-body>Hello</mj-body></mjml>');

        $binary = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(FIXTURES_DIR . '/mjml-binary.php');
        $decorator = new MjmlDecorator($mail, new BinaryRenderer($binary));

        Assert::equal('<html><body>Hello</body></html>', $decorator->getHtmlBody());
    }

    public function testBinaryRenderer_Error()
    {
        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getHtmlBody')->andReturn('<mjml><mj-invalid/></mjml>');

        $binary = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(FIXTURES_DIR . '/mjml-binary.php');
        $decorator = new MjmlDecorator($mail, new BinaryRenderer($binary));

        Assert::exception(fn() => $decorator->getHtmlBody(), ProcessException::class, '~mj-invalid~');
    }

    protected function tearDown()
    {
        \Mockery::close();
    }
}

(new MjmlDecoratorTest())->run();
