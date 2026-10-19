<?php

namespace Tests\Mailino\Extensions;

use Nette\InvalidArgumentException;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Extensions\ExtensionRegistry;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\MjmlBinary;
use Varhall\Mailino\Extensions\Prefix;
use Varhall\Mailino\Mailino;

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

        $mail = $container->getByType(Mailino::class)->create('html/test')->extend(CustomExtension::class);

        Assert::same('custom', $mail->getSubject());
    }

    public function testMailino_MailWithoutContainer()
    {
        $mail = $this->getContainer()->getByType(Mailino::class)->create('html/test');

        $properties = (fn() => [ $this->container, $this->registry ])->call($mail);

        Assert::null($properties[0]);
        Assert::type(ExtensionRegistry::class, $properties[1]);
    }
}

class CustomExtension implements IExtension
{
    public function extend(IMail $mail): IMail
    {
        return $mail->setSubject('custom');
    }
}

(new ExtensionRegistryTest())->run();
