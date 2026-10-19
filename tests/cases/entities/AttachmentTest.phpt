<?php

namespace Tests\Mailino\Entities;

use Nette\FileNotFoundException;
use Nette\Http\FileUpload;
use Nette\InvalidArgumentException;
use Nette\IOException;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Engine\HttpServer;
use Varhall\Mailino\Entities\Attachment;

require_once __DIR__ . '/../../bootstrap.php';

class AttachmentTest extends BaseTestCase
{
    public function getFileNames()
    {
        return [ [null], ['custom.txt'] ];
    }

    protected function setUp()
    {
        parent::setUp();
    }

    public function testConstruct()
    {
        $recipient = new Attachment('content', 'type', 'name');

        Assert::equal('content', $recipient->getContent());
        Assert::equal('type', $recipient->getContentType());
        Assert::equal('name', $recipient->getName());
    }

    /** @dataProvider getFileNames */
    public function testFromUpload($name)
    {
        $upload = new FileUpload([
            'name'      => 'name',
            'tmp_name'  => FIXTURES_DIR . '/attachment.txt',
            'size'      => 11,
            'error'     => UPLOAD_ERR_OK,
        ]);

        $attachment = Attachment::fromUpload($upload, $name);

        Assert::equal('hello world', $attachment->getContent());
        Assert::equal('text/plain', $attachment->getContentType());
        Assert::equal($name ?? 'name', $attachment->getName());
    }

    public function testFromContent()
    {
        $attachment = Attachment::fromContent('%PDF-1.4 fake', 'invoice.pdf');

        Assert::equal('%PDF-1.4 fake', $attachment->getContent());
        Assert::equal('application/pdf', $attachment->getContentType());
        Assert::equal('invoice.pdf', $attachment->getName());

        $attachment = Attachment::fromContent('a;b', 'data.csv', 'text/csv');
        Assert::equal('text/csv', $attachment->getContentType());
    }

    public function testFromPath_NotFound()
    {
        Assert::exception(fn() => Attachment::fromPath(FIXTURES_DIR . '/non-existent.txt'), FileNotFoundException::class);
    }

    public function testFromUrl()
    {
        $server = new HttpServer(FIXTURES_DIR . '/http/router.php');

        try {
            $attachment = Attachment::fromUrl($server->getUrl() . '/files/report.txt');

            Assert::equal('remote content', $attachment->getContent());
            Assert::equal('text/plain', $attachment->getContentType());
            Assert::equal('report.txt', $attachment->getName());

            Assert::exception(fn() => Attachment::fromUrl($server->getUrl() . '/404'), IOException::class);
        } finally {
            $server->stop();
        }
    }

    public function testFromUrl_InvalidScheme()
    {
        Assert::exception(fn() => Attachment::fromUrl('file:///etc/passwd'), InvalidArgumentException::class);
    }

    public function testFromPath_UrlRejected()
    {
        Assert::exception(fn() => Attachment::fromPath('https://example.com/a.txt'), InvalidArgumentException::class, '~fromUrl~');
        Assert::exception(fn() => Attachment::fromFile(new \SplFileInfo('ftp://example.com/a.txt')), InvalidArgumentException::class);
    }

    public function testWithName()
    {
        $attachment = new Attachment('content', 'text/plain', 'a.txt');
        $renamed = $attachment->withName('b.txt');

        Assert::equal('a.txt', $attachment->getName());
        Assert::equal('b.txt', $renamed->getName());
        Assert::equal('content', $renamed->getContent());
    }

    /** @dataProvider getFileNames */
    public function testFromPath($name)
    {
        $attachment = Attachment::fromPath(FIXTURES_DIR . '/attachment.txt', $name);

        Assert::equal('hello world', $attachment->getContent());
        Assert::equal('text/plain', $attachment->getContentType());
        Assert::equal($name ?? 'attachment.txt', $attachment->getName());
    }

    /** @dataProvider getFileNames */
    public function testFromFile($name)
    {
        $file = new \SplFileInfo(FIXTURES_DIR . '/attachment.txt');
        $attachment = Attachment::fromFile($file, $name);

        Assert::equal('hello world', $attachment->getContent());
        Assert::equal('text/plain', $attachment->getContentType());
        Assert::equal($name ?? 'attachment.txt', $attachment->getName());
    }
}

(new AttachmentTest())->run();
