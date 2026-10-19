<?php

namespace Tests\Mailino\Rendering;

use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Engine\Helpers;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\Rendering\RenderedMail;

require_once __DIR__ . '/../../bootstrap.php';

class MailRendererTest extends BaseTestCase
{
    public function testBodies()
    {
        $rendered = Helpers::renderer()->render(new Mail('html/test', [ 'data' => [ 'foo', 'bar' ] ]));

        Assert::contains('<strong>foo</strong>', $rendered->getHtmlBody());
        Assert::contains('<strong>bar</strong>', $rendered->getHtmlBody());
        Assert::contains('plain foo value', $rendered->getPlainBody());
        Assert::equal('html/test', $rendered->getTemplate());
    }

    public function testPlainBody_NotEscaped()
    {
        $rendered = Helpers::renderer()->render(new Mail('escape/test', [ 'name' => 'Tom & Jerry <x>', 'url' => 'https://test.cz/?a=1&b=2' ]));

        Assert::equal("Hello Tom & Jerry <x>, see https://test.cz/?a=1&b=2\n", $rendered->getPlainBody());
        Assert::equal("<p>Tom &amp; Jerry &lt;x&gt;</p>\n", $rendered->getHtmlBody());
    }

    public function testPlainBody_GeneratedFromHtml()
    {
        $rendered = Helpers::renderer()->render(new Mail('subject/test', [ 'name' => 'Pepa' ]));

        Assert::contains('Hi Pepa', $rendered->getPlainBody());
        Assert::notContains('<p>', $rendered->getPlainBody());
    }

    public function testPlainBody_GeneratedFromFinalHtml()
    {
        $extension = new class implements IExtension {
            public function process(RenderedMail $mail): RenderedMail
            {
                return $mail->withHtmlBody('<p>Transformed</p>');
            }
        };

        $rendered = Helpers::renderer([ $extension ])->render((new Mail('subject/test', [ 'name' => 'Pepa' ]))->extend(get_class($extension)));

        Assert::contains('Transformed', $rendered->getPlainBody());
    }

    public function getSubjects()
    {
        return [
            'explicit'          => [ (new Mail('subject/test', [ 'name' => 'Pepa' ]))->setSubject('Explicit'), 'Explicit' ],
            'explicit empty'    => [ (new Mail('subject/test', [ 'name' => 'Pepa' ]))->setSubject(''), '' ],
            'define block'      => [ new Mail('subject/test', [ 'name' => 'Pepa <b>' ]), 'Hello Pepa <b> & co' ],
            'html title'        => [ new Mail('title/test'), 'Title subject' ],
            'mjml title'        => [ new Mail('mjtitle/test', [ 'name' => 'Pepa' ]), 'MJML subject Pepa' ],
            'none'              => [ new Mail('html/test', [ 'data' => [] ]), '' ],
        ];
    }

    /** @dataProvider getSubjects */
    public function testSubject(Mail $mail, string $expected)
    {
        Assert::equal($expected, Helpers::renderer()->render($mail)->getSubject());
    }

    public function testSubjectBlockNotRenderedInBody()
    {
        $rendered = Helpers::renderer()->render(new Mail('subject/test', [ 'name' => 'Pepa' ]));

        Assert::notContains('Hello', $rendered->getHtmlBody());
    }

    public function testStrict()
    {
        Assert::exception(fn() => Helpers::renderer([], true)->render(new Mail('strict/test')), \ErrorException::class, '~Undefined variable \$missing~');
        Assert::error(fn() => Helpers::renderer([], false)->render(new Mail('strict/test')), E_WARNING);
    }

    public function testExtensionsOrder()
    {
        $first = new FirstExtension();
        $second = new SecondExtension();

        $mail = (new Mail('html/test', [ 'data' => [] ]))->setSubject('S');

        Assert::equal('S-first-second', Helpers::renderer([ $first, $second ])->render((clone $mail)->extend(FirstExtension::class)->extend(SecondExtension::class))->getSubject());
        Assert::equal('S-second-first', Helpers::renderer([ $first, $second ])->render((clone $mail)->extend(SecondExtension::class)->extend(FirstExtension::class))->getSubject());
    }

    public function testExtensionNotRegistered()
    {
        $mail = (new Mail('html/test', [ 'data' => [] ]))->extend(FirstExtension::class);

        Assert::exception(fn() => Helpers::renderer()->render($mail), InvalidArgumentException::class, '~not registered~');
    }

    public function testEnvelope()
    {
        $file = new Attachment('x', 'text/plain', 'x.txt');

        $mail = (new Mail('html/test', [ 'data' => [] ]))
            ->setFrom('from@test.cz', 'From')
            ->addTo('to@test.cz')
            ->addCc('cc@test.cz')
            ->addBcc('bcc@test.cz')
            ->addReplyTo('reply@test.cz')
            ->addFile($file)
            ->setReturnPath('bounce@test.cz')
            ->setPriority(2)
            ->addHeader('X-A', 'a')
            ->setBasePath('/tmp');

        $rendered = Helpers::renderer()->render($mail);

        Assert::equal(new Recipient('from@test.cz', 'From'), $rendered->getFrom());
        Assert::equal([ new Recipient('to@test.cz') ], $rendered->getTo());
        Assert::equal([ new Recipient('cc@test.cz') ], $rendered->getCc());
        Assert::equal([ new Recipient('bcc@test.cz') ], $rendered->getBcc());
        Assert::equal([ new Recipient('reply@test.cz') ], $rendered->getReplyTo());
        Assert::same([ $file ], $rendered->getFiles());
        Assert::equal('bounce@test.cz', $rendered->getReturnPath());
        Assert::equal(2, $rendered->getPriority());
        Assert::equal([ 'X-A' => 'a' ], $rendered->getHeaders());
        Assert::equal('/tmp', $rendered->getBasePath());
    }
}

class FirstExtension implements IExtension
{
    public function process(RenderedMail $mail): RenderedMail
    {
        return $mail->withSubject($mail->getSubject() . '-first');
    }
}

class SecondExtension implements IExtension
{
    public function process(RenderedMail $mail): RenderedMail
    {
        return $mail->withSubject($mail->getSubject() . '-second');
    }
}

(new MailRendererTest())->run();
