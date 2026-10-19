<?php

namespace Tests\Mailino\Extensions;

use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Extensions\ExtensionRegistry;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\MjmlBinary;
use Varhall\Mailino\Extensions\Prefix;
use Varhall\Mailino\Mailino;
use Varhall\Mailino\Rendering\RenderedMail;

require_once __DIR__ . '/../../bootstrap.php';

class ExtensionRegistryTest extends ContainerTestCase
{
    public function testGet()
    {
        $extension = new CustomExtension();
        $registry = new ExtensionRegistry([ $extension ]);

        Assert::true($registry->has(CustomExtension::class));
        Assert::same($extension, $registry->get(CustomExtension::class));
        Assert::same($extension, $registry->get(IExtension::class));
        Assert::equal([ CustomExtension::class => $extension ], $registry->all());
        Assert::false($registry->has(Prefix::class));
        Assert::exception(fn() => $registry->get(Prefix::class), InvalidArgumentException::class, '~not registered~');
    }

    public function testContainer_BuiltIn()
    {
        $registry = $this->getContainer()->getByType(ExtensionRegistry::class);

        Assert::true($registry->has(Prefix::class));
        Assert::true($registry->has(MjmlApi::class));
        Assert::true($registry->has(MjmlBinary::class));
    }

    public function testContainer_ApplicationExtension()
    {
        $container = $this->createContainer([ 'services' => [ CustomExtension::class ] ]);
        $mailino = $container->getByType(Mailino::class);

        $mail = $mailino->create('html/test', [ 'data' => [] ])->setSubject('Hello')->extend(CustomExtension::class);

        Assert::same('Hello (custom)', $mailino->preview($mail)->getSubject());
    }
}

class CustomExtension implements IExtension
{
    public function process(RenderedMail $mail): RenderedMail
    {
        return $mail->withSubject($mail->getSubject() . ' (custom)');
    }
}

(new ExtensionRegistryTest())->run();
