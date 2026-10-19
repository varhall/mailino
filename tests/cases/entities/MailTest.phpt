<?php

namespace Tests\Mailino\Entities;

use Latte\Engine;
use Nette\Http\FileUpload;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Nette\NotSupportedException;
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

    protected function createMail(bool $strict = false): Mail
    {
        return new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates', $strict);
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
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

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

        $upload = new FileUpload([
            'name'      => 'attachment.txt',
            'tmp_name'  => FIXTURES_DIR . '/attachment.txt',
            'size'      => 11,
            'error'     => UPLOAD_ERR_OK,
        ]);

        $mail->addFile($upload, $name);
        $mail->addFile(FIXTURES_DIR . '/attachment.txt', $name);
        $mail->addFile(new \SplFileInfo(FIXTURES_DIR . '/attachment.txt'), $name);
        $mail->addFile(new Attachment('hello world', 'text/plain', 'attachment.txt'), $name);

        Assert::count(4, $mail->getFiles());


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

    public function testReplyTo()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        $mail->addReplyTo('reply1@test.cz', 'Reply One');
        $mail->addReplyTo('reply2@test.cz');

        Assert::equal([
            new Recipient('reply1@test.cz', 'Reply One'),
            new Recipient('reply2@test.cz'),
        ], $mail->getReplyTo());
    }

    public function testReturnPath()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        Assert::null($mail->getReturnPath());

        $mail->setReturnPath('bounce@test.cz');
        Assert::equal('bounce@test.cz', $mail->getReturnPath());
    }

    public function testPriority()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        Assert::null($mail->getPriority());

        $mail->setPriority(1);
        Assert::equal(1, $mail->getPriority());
    }

    public function testHeaders()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        Assert::equal([], $mail->getHeaders());
        Assert::null($mail->getHeader('X-Custom'));

        $mail->addHeader('X-Custom', 'value1');
        $mail->addHeader('X-Another', 'value2');

        Assert::equal('value1', $mail->getHeader('X-Custom'));
        Assert::equal('value2', $mail->getHeader('X-Another'));
        Assert::equal(['X-Custom' => 'value1', 'X-Another' => 'value2'], $mail->getHeaders());
    }

    public function testHeaders_Overwrite()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        $mail->addHeader('X-Custom', 'first');
        $mail->addHeader('X-Custom', 'second');

        Assert::equal('second', $mail->getHeader('X-Custom'));
    }

    public function testExtend_Single()
    {
        $mail = new Mail($this->getEngine(), $this->getContainer(), FIXTURES_DIR . '/templates');

        $ext = $mail->extend(Prefix::class);
        Assert::type(PrefixDecorator::class, $ext);
    }

    public function testAttachments_Unsupported()
    {
        $mail = $this->createMail();

        Assert::exception(fn() => $mail->addFile(123), NotSupportedException::class);
    }

    public function testDefaults()
    {
        $mail = $this->createMail();

        Assert::equal('', $mail->getSubject());
        Assert::equal('', $mail->getTemplate());
        Assert::null($mail->getBasePath());
        Assert::exception(fn() => $mail->getFrom(), InvalidStateException::class, 'Sender (from) is not set');
    }

    public function testPlainBody_Escaping()
    {
        $mail = $this->createMail()
            ->setTemplate('escape/test')
            ->setData([ 'name' => 'Tom & Jerry <x>', 'url' => 'https://test.cz/?a=1&b=2' ]);

        Assert::equal("Hello Tom & Jerry <x>, see https://test.cz/?a=1&b=2\n", $mail->getPlainBody());
        Assert::equal("<p>Tom &amp; Jerry &lt;x&gt;</p>\n", $mail->getHtmlBody());
    }

    public function testPlainBody_Optional()
    {
        $mail = $this->createMail()->setTemplate('subject/test')->setData([ 'name' => 'Pepa' ]);

        Assert::equal('', $mail->getPlainBody());
    }

    public function testSubject_FromTemplate()
    {
        $mail = $this->createMail()->setTemplate('subject/test')->setData([ 'name' => 'Pepa <b>' ]);

        Assert::equal('Hello Pepa <b> & co', $mail->getSubject());
        Assert::notContains('Hello', $mail->getHtmlBody());

        $mail->setSubject('Explicit');
        Assert::equal('Explicit', $mail->getSubject());
    }

    public function testSubject_TemplateWithoutBlock()
    {
        $mail = $this->createMail()->setTemplate('html/test')->setData([ 'data' => [] ]);

        Assert::equal('', $mail->getSubject());
    }

    public function testStrict()
    {
        $mail = $this->createMail(true)->setTemplate('strict/test');
        Assert::exception(fn() => $mail->getHtmlBody(), \ErrorException::class, '~Undefined variable \$missing~');

        $mail = $this->createMail(false)->setTemplate('strict/test');
        Assert::error(fn() => $mail->getHtmlBody(), E_WARNING);
    }

    public function testClearRecipients()
    {
        $mail = $this->createMail()
            ->addTo('to@test.cz')->addCc('cc@test.cz')->addBcc('bcc@test.cz')->addReplyTo('reply@test.cz');

        $mail->clearTo()->clearCc()->clearBcc()->clearReplyTo();

        Assert::equal([], $mail->getTo());
        Assert::equal([], $mail->getCc());
        Assert::equal([], $mail->getBcc());
        Assert::equal([], $mail->getReplyTo());
    }

    public function testRemoveHeader()
    {
        $mail = $this->createMail()->addHeader('X-A', 'a')->addHeader('X-B', 'b');
        $mail->removeHeader('X-A');

        Assert::equal([ 'X-B' => 'b' ], $mail->getHeaders());
    }

    public function testBasePath()
    {
        $mail = $this->createMail()->setBasePath('/tmp');

        Assert::equal('/tmp', $mail->getBasePath());
    }

    public function testExtend_NotRegistered()
    {
        Assert::exception(fn() => $this->createMail()->extend(\stdClass::class), InvalidArgumentException::class, '~not registered~');
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
