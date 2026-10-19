<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions;

use Nette\InvalidArgumentException;

/**
 * Registry of mail extensions. All services implementing IExtension are registered automatically
 * by MailinoExtension, so mail entity does not need DI container to resolve extensions.
 */
class ExtensionRegistry
{
    /** @var array<class-string, IExtension> */
    private array $extensions = [];

    /**
     * @param IExtension[] $extensions
     */
    public function __construct(array $extensions = [])
    {
        foreach ($extensions as $extension) {
            $this->add($extension);
        }
    }

    public function add(IExtension $extension): void
    {
        $this->extensions[get_class($extension)] = $extension;
    }

    public function has(string $class): bool
    {
        return $this->find($class) !== null;
    }

    public function get(string $class): IExtension
    {
        $extension = $this->find($class);

        if ($extension === null) {
            throw new InvalidArgumentException(
                "Mail extension '{$class}' is not registered, register it as a service implementing " . IExtension::class
            );
        }

        return $extension;
    }

    /**
     * @return array<class-string, IExtension>
     */
    public function all(): array
    {
        return $this->extensions;
    }

    private function find(string $class): ?IExtension
    {
        if (isset($this->extensions[$class])) {
            return $this->extensions[$class];
        }

        // parent class or interface of registered extension
        foreach ($this->extensions as $extension) {
            if ($extension instanceof $class) {
                return $extension;
            }
        }

        return null;
    }
}
