<?php

namespace Tests\Mailino;

use Latte\Engine;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Extensions\Decorators\PrefixDecorator;
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
            'default_extensions'    => [ \Varhall\Mailino\Extensions\Prefix::class ],
            'embed_dir'             => FIXTURES_DIR . '/templates/embed',
        ] ]);

        $mail = $container->getByType(Mailino::class)->create('html/test')->setSubject('Hello');

        Assert::type(PrefixDecorator::class, $mail);
        Assert::equal('[TST] Hello', $mail->getSubject());
        Assert::equal(FIXTURES_DIR . '/templates/embed', $mail->getBasePath());

        // explicit extend of the same extension does not duplicate prefix
        Assert::equal('[TST] Hello', $mail->extend(\Varhall\Mailino\Extensions\Prefix::class)->getSubject());
    }

    public function testCreate_Strict()
    {
        $container = $this->createContainer([ 'mailino' => [ 'strict' => true ] ]);

        $mail = $container->getByType(Mailino::class)->create('strict/test');

        Assert::exception(fn() => $mail->getHtmlBody(), \ErrorException::class);
    }

    public function testPreview()
    {
        $mailino = $this->getContainer()->getByType(Mailino::class);

        $preview = $mailino->preview($mailino->create('subject/test', [ 'name' => 'Pepa' ]));

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

        $latte = $container->getByType(ILatteEngineFactory::class)->createLatte();
        $latte->renderToString(FIXTURES_DIR . '/templates/html/test.html.latte', [ 'data' => [] ]);

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
