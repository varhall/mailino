<?php

namespace Varhall\Mailino\Tests\Integration;

use Tester\Assert;
use Tests\Engine\BaseIntegrationTestCase;
use Varhall\Mailino\Services\HtmlMailService;

require_once __DIR__ . '/../../bootstrap.php';

class HtmlTest extends BaseIntegrationTestCase
{
    public function testSend()
    {
        /** @var HtmlMailService $service */
        $service = $this->container->getByType(HtmlMailService::class);
        $mail = $service->createMail('html/test', [ 'data' => ['foo', 'bar', 'baz'] ]);

        $service->onSuccess[] = function($mail) {
            Assert::contains('<strong>foo</strong>', $mail->getHtmlBody());
            Assert::contains('<strong>bar</strong>', $mail->getHtmlBody());
            Assert::contains('<strong>baz</strong>', $mail->getHtmlBody());

            Assert::contains('foo', $mail->getBody());
            Assert::contains('bar', $mail->getBody());
            Assert::contains('baz', $mail->getBody());
        };
        $service->send($mail);
    }
}

(new HtmlTest())->run();
