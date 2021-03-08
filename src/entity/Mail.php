<?php

namespace Varhall\Mailino\Entity;

use Nette\Mail\Message;

/**
 * @method array getFrom()
 * @method Mail setFrom(string $email, string $name)
 * @method string getSubject()
 * @method Mail addTo(string $email)
 * @method Mail addCc(string $email)
 * @method Mail addBcc(string $email)
 */
class Mail
{
    /** @var Message */
    protected $message;

    /** @var string */
    protected $prefix = null;

    /** @var string */
    protected $template;

    /** @var array */
    protected $data = [];


    public function __construct(string $template, array $data = [], ?Message $message = null)
    {
        $this->template = $template;
        $this->data = $data;
        $this->message = $message ?? new Message();
    }

    public function __call($method, $args)
    {
        if (!method_exists($this->message, $method))
            throw new \BadMethodCallException("Method {$method} does not exist");

        $result = call_user_func_array([ $this->message, $method], $args);
        return preg_match('/^get/i', $method) ? $result : $this;
    }


    /// GETTERS & SETTERS

    public function getSubjectPrefix(): string
    {
        return $this->prefix;
    }

    public function setSubjectPrefix(string $prefix): Mail
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function setSubject(string $subject, string $prefix = null): Mail
    {
        if ($prefix === null)
            $prefix = $this->prefix;

        if (!empty($prefix))
            $subject = "[{$prefix}] {$subject}";

        $this->message->setSubject($subject);
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): Mail
    {
        $this->data = $data;
        return $this;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): Mail
    {
        $this->template = $template;
        return $this;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function addFile($file): Message
    {
        if ($file instanceof \Nette\Http\FileUpload)
            $this->message->addAttachment($file->getUntrustedName(), $file->getContents(), $file->getContentType());

        else if ($file instanceof \SplFileInfo)
            $this->message->addAttachment($file->getFilename(), file_get_contents($file->getFilename()), mime_content_type($file->getFilename()));

        return $this;
    }
}