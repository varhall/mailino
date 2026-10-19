<?php

namespace Tests\Mailino\Extensions;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Config;
use Varhall\Mailino\Extensions\Prefix;
use Varhall\Mailino\Rendering\RenderedMail;

require_once __DIR__ . '/../../bootstrap.php';

class PrefixTest extends BaseTestCase
{
    private function prefix(string $subject): Prefix
    {
        return new Prefix(new Config((object) [ 'extensions' => [ 'prefix' => [ 'subject' => $subject ] ] ]));
    }

    private function mail(string $subject): RenderedMail
    {
        return new RenderedMail('tpl', $subject, '', '');
    }

    public function testProcess()
    {
        Assert::equal('[TST] Hello', $this->prefix('TST')->process($this->mail('Hello'))->getSubject());
    }

    public function testProcess_Idempotent()
    {
        $prefix = $this->prefix('TST');

        Assert::equal('[TST] Hello', $prefix->process($prefix->process($this->mail('Hello')))->getSubject());
    }

    public function testProcess_DifferentPrefixes()
    {
        Assert::equal('[B] [A] Hello', $this->prefix('B')->process($this->prefix('A')->process($this->mail('Hello')))->getSubject());
    }

    public function testProcess_EmptySubject()
    {
        Assert::equal('[TST]', $this->prefix('TST')->process($this->mail(''))->getSubject());
    }

    public function testProcess_EmptyPrefix()
    {
        Assert::equal('Hello', $this->prefix('')->process($this->mail('Hello'))->getSubject());
    }
}

(new PrefixTest())->run();
