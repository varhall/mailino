<?php

namespace Varhall\Mailino\Tests\Unit\Entity;

use Nette\Http\FileUpload;
use Nette\Mail\Message;
use Nette\Mail\MimePart;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;
use Varhall\Mailino\Entity\Mail;

require_once __DIR__ . '/../../../bootstrap.php';

class MailTest extends TestCase
{
    public function testConstruct()
    {
        $data = [ 'name' => 'Karl' ];
        $mail = new Mail('html/test', $data);

        Assert::same($data, $mail->getData());
        Assert::equal('html/test', $mail->getTemplate());
    }

    public function testSubjectSimple()
    {
        $mail = new Mail('html/test', []);

        $mail->setSubject('test');
        Assert::equal('test', $mail->getSubject());
    }

    public function testSubjectPrefix()
    {
        $mail = new Mail('html/test', []);

        $mail->setSubjectPrefix('TST');
        $mail->setSubject('test');

        Assert::equal('[TST] test', $mail->getSubject());
    }

    public function testSubjectPrefixOverride()
    {
        $mail = new Mail('html/test', []);

        $mail->setSubjectPrefix('TST');
        $mail->setSubject('test', 'CUST');

        Assert::equal('[CUST] test', $mail->getSubject());
    }

    public function testSubjectPrefixClear()
    {
        $mail = new Mail('html/test', []);

        $mail->setSubjectPrefix('TST');
        $mail->setSubject('test', '');

        Assert::equal('test', $mail->getSubject());
    }

    public function testAddTo()
    {
        $message = \Mockery::mock(Message::class);
        $message->shouldReceive('addTo');

        $mail = new Mail('html/test', [], $message);

        $mail->addTo('foo@test.com');
        $mail->addTo('bar@test.com', 'bar');

        Assert::equal([ 'foo@test.com' => null, 'bar@test.com' => 'bar' ], $mail->getTo());
    }

    public function testAddFile_FileUpload()
    {
        $message = \Mockery::mock(Message::class);
        $message->shouldReceive('addAttachment')
            ->andReturnUsing(function($filename, $content, $type) {
                Assert::equal('name', $filename);
                Assert::equal('contents', $content);
                Assert::equal('contenttype', $type);

                return \Mockery::mock(MimePart::class);
            });

        $file = \Mockery::mock(FileUpload::class);
        $file->shouldReceive('getUntrustedName')->andReturn('name');
        $file->shouldReceive('getContents')->andReturn('contents');
        $file->shouldReceive('getContentType')->andReturn('contenttype');

        $mail = new Mail('html/test', [], $message);
        $mail->addFile($file);
    }

    public function testAddFile_string()
    {
        $file = FIXTURES_DIR . '/attachment.txt';

        $message = \Mockery::mock(Message::class);
        $message->shouldReceive('addAttachment')
            ->andReturnUsing(function($filename, $content, $type) {
                Assert::equal('attachment.txt', $filename);
                Assert::equal('hello world', $content);
                Assert::equal('text/plain', $type);

                return \Mockery::mock(MimePart::class);
            });

        $mail = new Mail('html/test', [], $message);
        $mail->addFile($file);
    }

    public function testAddFile_local()
    {
        $file = FIXTURES_DIR . '/attachment.txt';

        $message = \Mockery::mock(Message::class);
        $message->shouldReceive('addAttachment')
            ->andReturnUsing(function($filename, $content, $type) {
                Assert::equal('attachment.txt', $filename);
                Assert::equal('hello world', $content);
                Assert::equal('text/plain', $type);

                return \Mockery::mock(MimePart::class);
            });

        $mail = new Mail('html/test', [], $message);
        $mail->addFile(new \SplFileInfo($file));
    }
}

(new MailTest())->run();
