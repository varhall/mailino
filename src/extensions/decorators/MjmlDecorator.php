<?php

namespace Varhall\Mailino\Extensions\Decorators;

use Qferrer\Mjml\RendererInterface;
use Varhall\Mailino\Entities\IMail;

class MjmlDecorator extends MailDecorator
{
    /** @var RendererInterface */
    protected $mjml;

    public function __construct(IMail $mail, RendererInterface $mjml)
    {
        parent::__construct($mail);

        $this->mjml = $mjml;
    }

    public function getHtmlBody(): string
    {
        return $this->mjml->render(parent::getHtmlBody());
    }
}