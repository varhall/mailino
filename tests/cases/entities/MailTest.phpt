<?php

namespace Tests\Mailino\Entities;

use Nette\Http\FileUpload;
use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\Prefix;

require_once __DIR__ . '/../../bootstrap.php';

class MailTest extends BaseTestCase
{
    public function getRecipientTypes()
    {
        return [ [ 'To' ], [ 'Cc' ], [ 'Bcc' ], [ 'ReplyTo' ] ];
    }

    public function testDefaults()
    {
        $mail = new Mail('accounts/register', [ 'foo' => 'bar' ]);

        Assert::equal('accounts/register', $mail->getTemplate());
        Assert::equal([ 'foo' => 'bar' ], $mail->getData());
        Assert::null($mail->getSubject());
        Assert::null($mail->getFrom());
        Assert::null($mail->getReturnPath());
        Assert::null($mail->getPriority());
        Assert::null($mail->getBasePath());
        Assert::equal([], $mail->getHeaders());
        Assert::equal([], $mail->getFiles());
        Assert::equal([], $mail->getExtensions());
    }

    public function testSetters()
    {
        $mail = (new Mail('a'))
            ->setTemplate('b')
            ->setData([ 'x' => 1 ])
            ->setSubject('Subject')
            ->setFrom('from@test.cz', 'From')
            ->setReturnPath('bounce@test.cz')
            ->setPriority(1)
            ->setBasePath('/tmp');

        Assert::equal('b', $mail->getTemplate());
        Assert::equal([ 'x' => 1 ], $mail->getData());
        Assert::equal('Subject', $mail->getSubject());
        Assert::equal(new Recipient('from@test.cz', 'From'), $mail->getFrom());
        Assert::equal('bounce@test.cz', $mail->getReturnPath());
        Assert::equal(1, $mail->getPriority());
        Assert::equal('/tmp', $mail->getBasePath());
    }

    /** @dataProvider getRecipientTypes */
    public function testRecipients(string $type)
    {
        $mail = new Mail('a');

        $mail->{"add{$type}"}('pepa@test.cz', 'Pepa')->{"add{$type}"}('karel@test.cz');

        Assert::equal([ new Recipient('pepa@test.cz', 'Pepa'), new Recipient('karel@test.cz') ], $mail->{"get{$type}"}());

        $mail->{"clear{$type}"}();
        Assert::equal([], $mail->{"get{$type}"}());
    }

    public function testFiles()
    {
        $upload = new FileUpload([
            'name'      => 'upload.txt',
            'tmp_name'  => FIXTURES_DIR . '/attachment.txt',
            'size'      => 11,
            'error'     => UPLOAD_ERR_OK,
        ]);

        $mail = (new Mail('a'))
            ->addFile(new Attachment('hello world', 'text/plain', 'attachment.txt'))
            ->addFile(new Attachment('hello world', 'text/plain', 'attachment.txt'), 'renamed.txt')
            ->addFile($upload)
            ->addFile(FIXTURES_DIR . '/attachment.txt')
            ->addFile(new \SplFileInfo(FIXTURES_DIR . '/attachment.txt'), 'info.txt');

        Assert::equal(
            [ 'attachment.txt', 'renamed.txt', 'upload.txt', 'attachment.txt', 'info.txt' ],
            array_map(fn(Attachment $file) => $file->getName(), $mail->getFiles())
        );

        foreach ($mail->getFiles() as $file) {
            Assert::equal('hello world', $file->getContent());
        }

        $mail->clearFiles();
        Assert::equal([], $mail->getFiles());
    }

    public function testFiles_UrlRejected()
    {
        Assert::exception(fn() => (new Mail('a'))->addFile('https://example.com/file.pdf'), InvalidArgumentException::class, '~fromUrl~');
    }

    public function testHeaders()
    {
        $mail = (new Mail('a'))->addHeader('X-A', 'a')->addHeader('X-B', 'b')->addHeader('X-A', 'c');

        Assert::equal('c', $mail->getHeader('X-A'));
        Assert::null($mail->getHeader('X-C'));

        $mail->removeHeader('X-A');
        Assert::equal([ 'X-B' => 'b' ], $mail->getHeaders());
    }

    public function testExtensions()
    {
        $mail = (new Mail('a'))->extend(Prefix::class)->extend(MjmlApi::class)->extend(Prefix::class);

        Assert::equal([ Prefix::class, MjmlApi::class ], $mail->getExtensions());
        Assert::true($mail->hasExtension(Prefix::class));

        $mail->removeExtension(Prefix::class);
        Assert::equal([ MjmlApi::class ], $mail->getExtensions());
        Assert::false($mail->hasExtension(Prefix::class));
    }

    public function testFluentReturnsSameInstance()
    {
        $mail = new Mail('a');

        Assert::same($mail, $mail->extend(Prefix::class));
        Assert::same($mail, $mail->addTo('a@test.cz'));
    }
}

(new MailTest())->run();
