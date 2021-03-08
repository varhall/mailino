<?php

namespace Varhall\Mailino\Services;

use Varhall\Mailino\Entity\Mail;

class MjmlMailService extends AbstractMailService
{
    protected function plainBody(Mail $mail): string
    {
        return $this->mailino->getLatte()->renderToString($this->templateFile($mail, 'plain.latte'), $mail->getData());
    }

    protected function htmlBody(Mail $mail): string
    {
        $mjml = $this->mailino->getLatte()->renderToString($this->templateFile($mail, 'mjml.latte'), $mail->getData());
        return $this->mailino->getRenderer()->render($mjml);
    }
}