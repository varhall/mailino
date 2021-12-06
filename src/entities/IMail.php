<?php

namespace Varhall\Mailino\Entities;

interface IMail
{
    public function getFrom(): Recipient;
    
    public function setFrom(string $email, string $name = null): IMail;
    
    public function getSubject(): string;

    public function setSubject(string $subject): IMail;

    public function getData(): array;

    public function setData(array $data): IMail;

    public function getTemplate(): string;

    public function setTemplate(string $template): IMail;

    public function getHtmlBody(): string;

    public function getPlainBody(): string;

    public function getTo(): array;

    public function addTo(string $email, string $name = null): IMail;

    public function getCc(): array;

    public function addCc(string $email, string $name = null): IMail;

    public function getBcc(): array;

    public function addBcc(string $email, string $name = null): IMail;

    public function getFiles(): array;

    public function addFile($file, string $name = null): IMail;

    public function extend(string $class): IMail;

}