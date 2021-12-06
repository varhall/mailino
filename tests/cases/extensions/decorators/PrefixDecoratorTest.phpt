<?php

namespace Tests\Mailino\Extensions\Decorators;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\Decorators\PrefixDecorator;


require_once __DIR__ . '/../../../bootstrap.php';

class PrefixDecoratorTest extends BaseTestCase
{
    public function testGetSubject_prefix()
    {
        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getSubject')->andReturn('subject');

        $decorator = new PrefixDecorator($mail, [ 'subject' => 'test' ]);

        Assert::equal('[test] subject', $decorator->getSubject());
    }

    public function testGetSubject_empty()
    {
        $mail = \Mockery::mock(Mail::class);
        $mail->shouldReceive('getSubject')->andReturn('subject');

        $decorator = new PrefixDecorator($mail);

        Assert::equal('subject', $decorator->getSubject());
    }
}

(new PrefixDecoratorTest())->run();
