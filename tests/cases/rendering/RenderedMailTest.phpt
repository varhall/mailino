<?php

namespace Tests\Mailino\Rendering;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Rendering\RenderedMail;

require_once __DIR__ . '/../../bootstrap.php';

class RenderedMailTest extends BaseTestCase
{
    private function createMail(): RenderedMail
    {
        return new RenderedMail('tpl', 'Subject', '<p>html</p>', 'plain', new Recipient('from@test.cz'), [ new Recipient('to@test.cz') ], headers: [ 'X-A' => 'a' ]);
    }

    public function testWithers()
    {
        $mail = $this->createMail();
        $file = new Attachment('x', 'text/plain', 'x.txt');

        $changed = $mail
            ->withSubject('New')
            ->withHtmlBody('<p>new</p>')
            ->withPlainBody('new')
            ->withFrom(new Recipient('other@test.cz'))
            ->withTo([ 5 => new Recipient('a@test.cz') ])
            ->withCc([ new Recipient('b@test.cz') ])
            ->withBcc([ new Recipient('c@test.cz') ])
            ->withReplyTo([ new Recipient('d@test.cz') ])
            ->withFiles([ $file ])
            ->withReturnPath('bounce@test.cz')
            ->withPriority(1)
            ->withHeader('X-B', 'b');

        Assert::equal('New', $changed->getSubject());
        Assert::equal('<p>new</p>', $changed->getHtmlBody());
        Assert::equal('new', $changed->getPlainBody());
        Assert::equal('other@test.cz', $changed->getFrom()->getEmail());
        Assert::equal([ new Recipient('a@test.cz') ], $changed->getTo());
        Assert::equal('b@test.cz', $changed->getCc()[0]->getEmail());
        Assert::equal('c@test.cz', $changed->getBcc()[0]->getEmail());
        Assert::equal('d@test.cz', $changed->getReplyTo()[0]->getEmail());
        Assert::same([ $file ], $changed->getFiles());
        Assert::equal('bounce@test.cz', $changed->getReturnPath());
        Assert::equal(1, $changed->getPriority());
        Assert::equal([ 'X-A' => 'a', 'X-B' => 'b' ], $changed->getHeaders());
        Assert::equal('tpl', $changed->getTemplate());

        // original is unchanged
        Assert::equal('Subject', $mail->getSubject());
        Assert::equal('to@test.cz', $mail->getTo()[0]->getEmail());
        Assert::equal([ 'X-A' => 'a' ], $mail->getHeaders());
    }

    public function testWithHeader_Remove()
    {
        $mail = $this->createMail()->withHeader('X-A', null);

        Assert::equal([], $mail->getHeaders());
        Assert::null($mail->getHeader('X-A'));
    }
}

(new RenderedMailTest())->run();
