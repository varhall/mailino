<?php

namespace Tests\Mailino\Extensions\Decorators;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Extensions\Decorators\MailDecorator;
use Varhall\Mailino\Extensions\IExtension;

require_once __DIR__ . '/../../../bootstrap.php';

class MailDecoratorTest extends BaseTestCase
{
    /** methods with own logic, tested separately */
    private const SKIPPED = [ 'extend' ];

    public function getMethods()
    {
        $methods = array_filter(
            (new \ReflectionClass(IMail::class))->getMethods(),
            fn(\ReflectionMethod $method) => !in_array($method->getName(), self::SKIPPED, true)
        );

        return array_map(fn(\ReflectionMethod $method) => [ $method->getName() ], array_values($methods));
    }

    /** @dataProvider getMethods */
    public function testDelegatesAllArguments(string $name)
    {
        $method = new \ReflectionMethod(IMail::class, $name);
        $args = array_map(fn(\ReflectionParameter $parameter) => $this->createArgument($parameter), $method->getParameters());

        $inner = \Mockery::mock(IMail::class);
        $decorator = new class ($inner) extends MailDecorator {
        };

        $returnType = (string) $method->getReturnType();
        $inner->shouldReceive($name)->once()->with(...$args)->andReturn($this->createReturnValue($returnType, $inner));

        $result = $decorator->$name(...$args);

        if ($returnType === IMail::class) {
            Assert::same($decorator, $result, "{$name}() should return decorator to keep fluent chain decorated");
        }
    }

    public function testExtend()
    {
        $inner = \Mockery::mock(IMail::class);
        $decorator = new class ($inner) extends MailDecorator {
        };

        $extension = \Mockery::mock(IExtension::class);
        $extension->shouldReceive('extend')->once()->with($decorator)->andReturn($decorator);
        $inner->shouldReceive('getExtensionFactory')->with('Foo')->andReturn($extension);

        Assert::same($decorator, $decorator->extend('Foo'));
    }

    private function createArgument(\ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if ($type === null) {
            return "file-{$parameter->getName()}";
        }

        return match ($type->getName()) {
            'int'       => 3,
            'bool'      => true,
            'array'     => [ 'key' => 'value' ],
            default     => "{$parameter->getName()}-value",
        };
    }

    private function createReturnValue(string $type, IMail $inner)
    {
        return match (ltrim($type, '?')) {
            IMail::class        => $inner,
            Recipient::class    => new Recipient('test@test.cz', 'Test'),
            IExtension::class   => \Mockery::mock(IExtension::class),
            'string'            => 'string',
            'int'               => 1,
            'array'             => [],
            default             => null,
        };
    }

    protected function tearDown()
    {
        \Mockery::close();
    }
}

(new MailDecoratorTest())->run();
