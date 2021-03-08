<?php

namespace Varhall\Mailino\Config;

class Sender
{
    /** @var string */
    protected $email;

    /** @var string */
    protected $name;


    public function __construct(string $email, string $name)
    {
        $this->email = $email;
        $this->name = $name;
    }


    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }
}