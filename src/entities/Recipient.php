<?php

namespace Varhall\Mailino\Entities;

class Recipient
{
    /** @var string */
    private $name;

    /** @var string */
    private $email;


    public function __construct(string $email, string $name = null)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}