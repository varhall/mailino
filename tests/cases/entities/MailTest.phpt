<?php

namespace Tests\Mailino\Entities;

use Latte\Engine;
use Nette\Http\FileUpload;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Extensions\Decorators\MjmlDecorator;
use Varhall\Mailino\Extensions\Decorators\PrefixDecorator;
use Varhall\Mailino\Extensions\MjmlBinary;
use Varhall\Mailino\Extensions\Prefix;


require_once __DIR__ . '/../../bootstrap.php';

class MailTest extends ContainerTestCase
{
    public function getRecipientTypes()
    {
        return [ ['To'], ['Cc'], ['Bcc'] ];
    }

    public function getFileNames()
    {
        return [ [null], ['custom.txt'] ];
    }

    protected function getEngine()
    {
        return new Engine();
    }

    public function testGetters()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        $mail->setFrom('pepa@novak.cz', 'Pepa Novak');
        Assert::equal('pepa@novak.cz', $mail->getFrom()->getEmail());
        Assert::equal('Pepa Novak', $mail->getFrom()->getName());

        $mail->setData([ 'foo' => 'bar', 'hello' => 'world' ]);
        Assert::equal([ 'foo' => 'bar', 'hello' => 'world' ], $mail->getData());

        $mail->setTemplate('template');
        Assert::equal('template', $mail->getTemplate());

        $mail->setSubject('subject');
        Assert::equal('subject', $mail->getSubject());
    }

    /** @dataProvider getRecipientTypes */
    public function testRecipients_Name($type)
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(),FIXTURES_DIR . '/templates');

        call_user_func([ $mail, "add{$type}" ], "pepa@{$type}.cz", "Pepa {$type}");
        call_user_func([ $mail,"add{$type}" ], "karel@{$type}.cz", "Karel {$type}");

        Assert::equal([
            new Recipient("pepa@{$type}.cz", "Pepa {$type}"),
            new Recipient("karel@{$type}.cz", "Karel {$type}")
        ], call_user_func([ $mail, "get{$type}" ]));
    }

    /** @dataProvider getRecipientTypes */
    public function testRecipients_Anonymous($type)
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        call_user_func([ $mail, "add{$type}" ], "pepa@{$type}.cz");
        call_user_func([ $mail,"add{$type}" ], "karel@{$type}.cz");

        Assert::equal([
            new Recipient("pepa@{$type}.cz"),
            new Recipient("karel@{$type}.cz")
        ], call_user_func([ $mail, "get{$type}" ]));
    }

    /** @dataProvider getFileNames */
    public function testAttachments($name)
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        $upload = \Mockery::mock(FileUpload::class);
        $upload->shouldReceive('getContents')->andReturn('hello world');
        $upload->shouldReceive('getContentType')->andReturn('text/plain');
        $upload->shouldReceive('getUntrustedName')->andReturn('attachment.txt');

        $mail->addFile($upload, $name);
        $mail->addFile(FIXTURES_DIR . '/attachment.txt', $name);
        $mail->addFile(new \SplFileInfo(FIXTURES_DIR . '/attachment.txt'), $name);


        $expected = new Attachment('hello world', 'text/plain', $name ?? 'attachment.txt');

        foreach ($mail->getFiles() as $file) {
            Assert::equal($expected, $file);
        }
    }

    public function testHtmlBody()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');
        $mail->setTemplate('html/test');
        $mail->setData([ 'data' => ['foo', 'bar', 'baz'] ]);

        Assert::contains('<strong>foo</strong>', $mail->getHtmlBody());
        Assert::contains('<strong>bar</strong>', $mail->getHtmlBody());
        Assert::contains('<strong>baz</strong>', $mail->getHtmlBody());
    }

    public function testPlainBody()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');
        $mail->setTemplate('html/test');
        $mail->setData([ 'data' => ['foo', 'bar', 'baz'] ]);

        Assert::contains('plain foo value', $mail->getPlainBody());
        Assert::contains('plain bar value', $mail->getPlainBody());
        Assert::contains('plain baz value', $mail->getPlainBody());
    }

    public function testExtend_Single()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        $ext = $mail->extend(Prefix::class);
        Assert::type(PrefixDecorator::class, $ext);
    }

    public function testExtend_Multiple()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        $ext = $mail->setSubject('hello')
                    ->extend(Prefix::class)
                    ->extend(MjmlBinary::class);

        Assert::type(MjmlDecorator::class, $ext);
        Assert::equal('[TST] hello', $ext->getSubject());
    }
}

(new MailTest())->run();
