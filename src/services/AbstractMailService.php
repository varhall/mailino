<?php

namespace Varhall\Mailino\Services;

use Nette\SmartObject;
use Varhall\Mailino\Entity\Mail;
use Varhall\Mailino\Mailino;

abstract class AbstractMailService
{
    use SmartObject;

    /** @var Mailino */
    protected $mailino;

    public $onSending   = [];
    public $onSuccess   = [];
    public $onError     = [];

    public function __construct(Mailino $mailino)
    {
        $this->mailino = $mailino;
    }

    
    public function createMail(string $template, array $data = []): Mail
    {
        $config = $this->mailino->getConfig();
        $mail = new Mail($template, $data);

        $mail->setSubjectPrefix($config->getSubjectPrefix());
        $mail->setFrom($config->getSender()->getEmail(), $config->getSender()->getName());

        return $mail;
    }

    public function send(Mail $mail): void
    {
        $this->onSending($mail);

        try {
            $message = $mail->getMessage();
            $message->setBody($this->plainBody($mail));
            $message->setHtmlBody($this->htmlBody($mail));

            if (!$this->mailino->getConfig()->isVerifySSL())
                $this->disableSSLVerification();

            $this->mailino->getMailer()->send($message);
            $this->onSuccess($mail);

        } catch (\Exception $ex) {
            $this->onError($mail, $ex);
            throw $ex;
        }
    }

    protected function disableSSLVerification()
    {
        stream_context_set_default([
            'ssl' => [
                'verify_peer'       => FALSE,
                'verify_peer_name'  => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
    }

    protected function templateFile(Mail $mail, string $extension = null): string
    {
        return implode('.', array_filter([ "{$this->mailino->getConfig()->getTemplateDir()}/{$mail->getTemplate()}", $extension ]));
    }

    protected abstract function plainBody(Mail $mail): string;
    
    protected abstract function htmlBody(Mail $mail): string;
}