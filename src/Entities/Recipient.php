<?php

declare(strict_types=1);

namespace Varhall\Mailino\Entities;

/**
 * Immutable mail address with optional display name
 */
final class Recipient
{
    public function __construct(
        private readonly string $email,
        private readonly ?string $name = null
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
