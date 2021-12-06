<?php

namespace Tests\Mailino\Entities;

use Nette\Http\FileUpload;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
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
        $upload = \Mockery::mock(FileUpload::class);
        $upload->shouldReceive('getContents')->andReturn('content');
        $upload->shouldReceive('getContentType')->andReturn('type');
        $upload->shouldReceive('getUntrustedName')->andReturn('name');

        $attachment = Attachment::fromUpload($upload, $name);

        Assert::equal('content', $attachment->getContent());
        Assert::equal('type', $attachment->getContentType());
        Assert::equal($name ?? 'name', $attachment->getName());
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
