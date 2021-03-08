<?php

namespace Varhall\Mailino\Services;

use Varhall\Mailino\Entity\Mail;

class HtmlMailService extends AbstractMailService
{
    protected function plainBody(Mail $mail): string
    {
        return $this->mailino->getLatte()->renderToString($this->templateFile($mail, 'plain.latte'), $mail->getData());
    }

    protected function htmlBody(Mail $mail): string
    {
        return $this->mailino->getLatte()->renderToString($this->templateFile($mail, 'html.latte'), $mail->getData());
    }
}