<?php

namespace Tests\Mailino;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\Prefix;
use Varhall\Mailino\Rendering\RenderedMail;
use Varhall\Mailino\ILatteEngineFactory;
use Varhall\Mailino\Mailino;
use Varhall\Mailino\NetteLatteEngineFactory;

require_once __DIR__ . '/../bootstrap.php';

class MailinoTest extends ContainerTestCase
{
    public function testCreate()
    {
        $mail = $this->getContainer()->getByType(Mailino::class)->create('html/test', [ 'data' => [ 'a' ] ]);

        Assert::equal('sender@test.com', $mail->getFrom()->getEmail());
        Assert::equal('html/test', $mail->getTemplate());
        Assert::equal([ 'data' => [ 'a' ] ], $mail->getData());
        Assert::equal('noreply@test.com', $mail->getReplyTo()[0]->getEmail());
        Assert::equal('bounce@test.com', $mail->getReturnPath());
        Assert::equal(3, $mail->getPriority());
        Assert::equal([ 'X-Custom' => 'test-value' ], $mail->getHeaders());
        Assert::null($mail->getBasePath());
    }

    public function testCreate_DefaultExtensions()
    {
        $container = $this->createContainer([ 'mailino' => [
            'default_extensions'    => [ Prefix::class ],
            'embed_dir'             => FIXTURES_DIR . '/templates/embed',
        ] ]);

        $mailino = $container->getByType(Mailino::class);
        $mail = $mailino->create('html/test', [ 'data' => [] ])->setSubject('Hello');

        Assert::type(Mail::class, $mail);
        Assert::equal([ Prefix::class ], $mail->getExtensions());
        Assert::equal(FIXTURES_DIR . '/templates/embed', $mail->getBasePath());
        Assert::equal('[TST] Hello', $mailino->preview($mail)->getSubject());

        // explicit extend of the same extension does not duplicate prefix
        Assert::equal('[TST] Hello', $mailino->preview($mail->extend(Prefix::class))->getSubject());
    }

    public function testCreate_Strict()
    {
        $container = $this->createContainer([ 'mailino' => [ 'strict' => true ] ]);
        $mailino = $container->getByType(Mailino::class);

        Assert::exception(fn() => $mailino->preview($mailino->create('strict/test')), \ErrorException::class);
    }

    public function testPreview()
    {
        $mailino = $this->getContainer()->getByType(Mailino::class);

        $preview = $mailino->preview($mailino->create('subject/test', [ 'name' => 'Pepa' ]));

        Assert::type(RenderedMail::class, $preview);
        Assert::equal('Hello Pepa & co', $preview->getSubject());
        Assert::contains('<p>Hi Pepa</p>', $preview->getHtmlBody());
        Assert::contains('Hi Pepa', $preview->getPlainBody());
        Assert::notContains('<p>', $preview->getPlainBody());
    }

    public function testPreview_TitleSubject()
    {
        $mailino = $this->getContainer()->getByType(Mailino::class);

        $preview = $mailino->preview($mailino->create('title/test'));

        Assert::equal('Title subject', $preview->getSubject());
    }

    public function testLatteEngineFactory_TempDir()
    {
        $container = $this->createContainer([ 'mailino' => [ 'temp_dir' => TMP_DIR . '/latte' ] ]);

        // unique template - template compiled earlier in the same process is not written to cache again
        $template = TMP_DIR . '/' . uniqid('template-') . '.latte';
        file_put_contents($template, '<p>{$value}</p>' . uniqid());

        $latte = $container->getByType(ILatteEngineFactory::class)->createLatte();
        $latte->renderToString($template, [ 'value' => 'x' ]);

        Assert::true(count(glob(TMP_DIR . '/latte/*.php')) > 0);
    }

    public function testNetteLatteEngineFactory()
    {
        $factory = new NetteLatteEngineFactory(new TestLatteFactory());

        Assert::true(array_key_exists('testfilter', array_change_key_case($factory->createLatte()->getFilters())));
    }

    public function testNetteLatteEngineFactory_Container()
    {
        $container = $this->createContainer([ 'services' => [
            'mailino.template_factory'  => NetteLatteEngineFactory::class,
        ] ]);

        $factory = $container->getByType(ILatteEngineFactory::class);

        Assert::type(NetteLatteEngineFactory::class, $factory);
        Assert::type(Engine::class, $factory->createLatte());
    }
}

class TestLatteFactory implements LatteFactory
{
    public function create(?\Nette\Application\UI\Control $control = null): Engine
    {
        return (new Engine())->addFilter('testFilter', fn($s) => $s);
    }
}

(new MailinoTest())->run();
