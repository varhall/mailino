<?php

namespace Varhall\Mailino\Tests\Integration;

use Tester\Assert;
use Tests\Engine\BaseIntegrationTestCase;
use Varhall\Mailino\Services\MjmlMailService;

require_once __DIR__ . '/../../bootstrap.php';

class MjmlTest extends BaseIntegrationTestCase
{
    public function testSend()
    {
        /** @var MjmlMailService $service */
        $service = $this->container->getByType(MjmlMailService::class);
        $mail = $service->createMail('mjml/test', [ 'data' => ['foo', 'bar', 'baz'] ]);

        $service->onSuccess[] = function($mail) {
            Assert::contains('.mj-outlook-group-fix { width:100% !important; }', $mail->getHtmlBody());
            Assert::contains('foo', $mail->getHtmlBody());
            Assert::contains('bar', $mail->getHtmlBody());
            Assert::contains('baz', $mail->getHtmlBody());

            Assert::contains('foo', $mail->getBody());
            Assert::contains('bar', $mail->getBody());
            Assert::contains('baz', $mail->getBody());
        };
        $service->send($mail);
    }
}

(new MjmlTest())->run();
