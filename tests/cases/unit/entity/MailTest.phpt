<?php

namespace Varhall\Mailino\Tests\Unit\Entity;

use Tester\Assert;
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
}

(new MailTest())->run();
