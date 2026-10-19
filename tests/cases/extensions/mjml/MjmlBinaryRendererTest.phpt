<?php

namespace Tests\Mailino\Extensions\Mjml;

use Qferrer\Mjml\Exception\ProcessException;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Engine\Php;
use Varhall\Mailino\Extensions\Mjml\MjmlBinaryRenderer;

require_once __DIR__ . '/../../../bootstrap.php';

class MjmlBinaryRendererTest extends BaseTestCase
{
    private function command(): array
    {
        return [ Php::binary(), FIXTURES_DIR . '/mjml-binary.php' ];
    }

    public function testRender_Array()
    {
        $renderer = new MjmlBinaryRenderer($this->command());

        Assert::equal('<html><body>Hello</body></html>', $renderer->render('<mjml><mj-body>Hello</mj-body></mjml>'));
    }

    public function testRender_ShellString()
    {
        $renderer = new MjmlBinaryRenderer(implode(' ', array_map('escapeshellarg', $this->command())));

        Assert::equal('<html><body>Hello</body></html>', $renderer->render('<mjml><mj-body>Hello</mj-body></mjml>'));
    }

    public function testRender_Arguments()
    {
        $renderer = new MjmlBinaryRenderer($this->command());

        Assert::equal(implode(' ', MjmlBinaryRenderer::ARGUMENTS), $renderer->render('<mj-args>'));
    }

    public function testRender_LargeOutput()
    {
        // larger than pipe buffers on both stdout and stderr - must not deadlock
        $renderer = new MjmlBinaryRenderer($this->command(), 10);
        $content = '<mjml><mj-body>' . str_repeat('x', 1024 * 1024) . '</mj-body></mjml><mj-noisy>';

        Assert::equal(1024 * 1024 + strlen('<html><body></body></html>'), strlen($renderer->render($content)));
    }

    public function testRender_Error()
    {
        $renderer = new MjmlBinaryRenderer($this->command());

        Assert::exception(fn() => $renderer->render('<mjml><mj-invalid/></mjml>'), ProcessException::class, '~mj-invalid~');
    }

    public function testRender_Timeout()
    {
        $renderer = new MjmlBinaryRenderer($this->command(), 1);

        $start = microtime(true);
        Assert::exception(fn() => $renderer->render('<mj-sleep>'), ProcessException::class, '~timed out~');
        Assert::true(microtime(true) - $start < 4);
    }

    public function testRender_NotFound()
    {
        $renderer = new MjmlBinaryRenderer('/non-existent/mjml');

        Assert::exception(fn() => $renderer->render('<mjml></mjml>'), ProcessException::class);
    }
}

(new MjmlBinaryRendererTest())->run();
