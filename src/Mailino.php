<?php

declare(strict_types=1);

namespace Varhall\Mailino;

use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Rendering\MailRenderer;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Creates mails with defaults from configuration and renders previews
 */
class Mailino
{
    public function __construct(
        private readonly Config $config,
        private readonly MailRenderer $renderer
    ) {
    }

    /**
     * @param string $template  template path relative to template directory, without extension
     * @param array<string, mixed> $data  template parameters
     */
    public function create(string $template, array $data = []): Mail
    {
        $from = $this->config->getValue('from');

        $mail = (new Mail($template, $data))
            ->setFrom($from->email, $from->name)
            ->setReturnPath($this->config->getValue('return_path'))
            ->setPriority($this->config->getValue('priority'))
            ->setBasePath($this->config->getValue('embed_dir'));

        $replyTo = $this->config->getValue('reply_to');
        if ($replyTo !== null) {
            $mail->addReplyTo($replyTo->email, $replyTo->name);
        }

        foreach ($this->config->getValue('headers') ?? [] as $name => $value) {
            $mail->addHeader($name, $value);
        }

        foreach ($this->config->getValue('default_extensions') ?? [] as $extension) {
            $mail->extend($extension);
        }

        return $mail;
    }

    /**
     * Renders mail subject and bodies without sending
     */
    public function preview(Mail $mail): RenderedMail
    {
        return $this->renderer->render($mail);
    }
}
