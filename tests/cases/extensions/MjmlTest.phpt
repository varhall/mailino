<?php

namespace Tests\Mailino\Extensions;

use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Tests\Engine\Php;
use Varhall\Mailino\Config;
use Varhall\Mailino\Extensions\Mjml\MjmlApiRenderer;
use Varhall\Mailino\Extensions\Mjml\MjmlBinaryRenderer;
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\MjmlBinary;
use Varhall\Mailino\Rendering\RenderedMail;

require_once __DIR__ . '/../../bootstrap.php';

class MjmlTest extends ContainerTestCase
{
    public function testMjmlApi_Config()
    {
        $renderer = $this->getContainer()->getByType(MjmlApi::class)->getRenderer();

        Assert::type(MjmlApiRenderer::class, $renderer);
        Assert::equal('https://mjml.local/v1', $renderer->getUrl());
    }

    public function testMjmlBinary_Config()
    {
        Assert::type(MjmlBinaryRenderer::class, $this->getContainer()->getByType(MjmlBinary::class)->getRenderer());
    }

    public function testMjmlBinary_Process()
    {
        $config = new Config((object) [ 'extensions' => [ 'mjml.binary' => [
            'binary'    => [ Php::binary(), FIXTURES_DIR . '/mjml-binary.php' ],
            'timeout'   => 10,
        ] ] ]);

        $mail = new RenderedMail('tpl', 'Subject', '<mjml><mj-body>Hello</mj-body></mjml>', 'plain');
        $rendered = (new MjmlBinary($config))->process($mail);

        Assert::equal('<html><body>Hello</body></html>', $rendered->getHtmlBody());
        Assert::equal('Subject', $rendered->getSubject());
        Assert::equal('plain', $rendered->getPlainBody());
    }
}

(new MjmlTest())->run();
