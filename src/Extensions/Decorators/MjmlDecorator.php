<?php

namespace Varhall\Mailino\Extensions\Decorators;

use Qferrer\Mjml\RendererInterface;
use Varhall\Mailino\Entities\IMail;

class MjmlDecorator extends MailDecorator
{
    protected RendererInterface $mjml;

    /** @var array{0: string, 1: string}|null  last rendered [mjml source, html] */
    private ?array $cache = null;

    public function __construct(IMail $mail, RendererInterface $mjml)
    {
        parent::__construct($mail);

        $this->mjml = $mjml;
    }

    public function getHtmlBody(): string
    {
        $source = parent::getHtmlBody();

        // MJML rendering is expensive (HTTP request / process), render again only when source changes
        if ($this->cache === null || $this->cache[0] !== $source) {
            $this->cache = [ $source, $this->mjml->render($source) ];
        }

        return $this->cache[1];
    }
}
