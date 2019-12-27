<?php

namespace Varhall\Mailino\Services;

use Nette\Mail\SmtpMailer;
use Varhall\Mailino\Models\Email;

/**
 * Class AbstractEmailsService
 * @package Varhall\Mailino
 */
abstract class AbstractEmailsService
{
    /**
     * @var \Nette\Mail\IMailer
     */
    private $mailer = NULL;

    private $senderName = '';
    
    private $senderEmail = '';

    private $subjectPrefix = '';
    
    private $templateDir = '';

    private $verifySsl = FALSE;
    
    
    public function __construct(\Nette\Mail\IMailer $mailer)
    {
        $this->mailer = $mailer;
    }




    //////////////////////////////////////// GETTERS & SETTERS /////////////////////////////////////////////////////////

    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * @param string $senderName
     */
    public function setSenderName($senderName)
    {
        $this->senderName = $senderName;
    }

    /**
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * @param string $senderEmail
     */
    public function setSenderEmail($senderEmail)
    {
        $this->senderEmail = $senderEmail;
    }

    /**
     * @return string
     */
    public function getSubjectPrefix()
    {
        return $this->subjectPrefix;
    }

    /**
     * @param string $subjectPrefix
     */
    public function setSubjectPrefix($subjectPrefix)
    {
        $this->subjectPrefix = $subjectPrefix;
    }

    /**
     * @return string
     */
    public function getTemplateDir()
    {
        return $this->templateDir;
    }

    /**
     * @param string $templateDir
     */
    public function setTemplateDir($templateDir)
    {
        $this->templateDir = $templateDir;
    }

    /**
     * @return bool
     */
    public function getVerifySsl()
    {
        return $this->verifySsl;
    }

    /**
     * @param bool $verifySsl
     */
    public function setVerifySsl($verifySsl)
    {
        $this->verifySsl = $verifySsl;
    }





    ///////////////////////////////////////// PUBLIC METHODS ///////////////////////////////////////////////////////////

    /**
     * Sends email message. This method loads template files defined in "template_dir" path. There have to be two files
     * for each template name (HTML and plain text versions). Arguments from associative $data array are passed to the
     * template files as standard variables. Sent email is stored after that.
     *
     * @param $recipient Recipient email
     * @param $subject Email subject
     * @param $template Name of template file - eg. 'welcome' - looks for 'welcome.html.latte' and 'welcome.plain.latte'
     * @param array $data Array of arguments
     * @param array $attachment Array of FileUploads
     */
    protected function sendMessage($recipient, $subject, $template, array $data, array $attachments = [])
    {
        $latte = $this->createTemplate();
   
        $html = $latte->renderToString($this->templateDir . "/{$template}.html.latte", $data);
        $plain = $latte->renderToString($this->templateDir . "/{$template}.plain.latte", $data);

        $prefix = !empty($this->subjectPrefix) ? "[{$this->subjectPrefix}] " : '';

        // send email
        
        $mail = new \Nette\Mail\Message;
        $mail->setFrom($this->senderEmail, $this->senderName)
            ->addTo($recipient)
            ->setSubject("{$prefix}{$subject}")
            ->setBody($plain)
            ->setHtmlBody($html);

        foreach ($attachments as $attachment) {
            if ($attachment instanceof \Nette\Http\FileUpload)
                $mail->addAttachment($attachment->getName(), $attachment->getContents(), $attachment->getContentType());
        }

        if (!$this->verifySsl)
            $this->disableSSLVerification();

        $this->mailer->send($mail);

        $this->saveEmail([
            'recipient'     => $recipient,
            'subject'       => $subject,
            'html_content'  => $html,
            'plain_content' => $plain,
            'raw_data'      => $data,
            'template'      => $template
        ]);
    }

    /**
     * Stores sent email message to the database
     *
     * @param $data
     * @return \Varhall\Dbino\Model
     */
    protected function saveEmail($data)
    {
        return Email::create($data);
    }

    /**
     * Creates Latte template
     * 
     * @return \Latte\Engine
     */
    protected function createTemplate()
    {
        return new \Latte\Engine();
    }

    private function disableSSLVerification()
    {
        stream_context_set_default([
            'ssl' => [
                'verify_peer'       => FALSE,
                'verify_peer_name'  => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
    }
}