<?php

namespace Varhall\Mailino;

use Nette\DI\Container;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Entities\Preview;
use Varhall\Mailino\Extensions\ExtensionRegistry;

class Mailino
{
    protected Container $container;

    protected Config $config;

    protected ILatteEngineFactory $latteFactory;

    protected ?ExtensionRegistry $registry;

    /**
     * @param Container $container  deprecated, kept for BC - extensions are resolved by ExtensionRegistry
     */
    public function __construct(
        Config $config,
        ILatteEngineFactory $latteFactory,
        Container $container,
        ?ExtensionRegistry $registry = null
    ) {
        $this->config = $config;
        $this->latteFactory = $latteFactory;
        $this->container = $container;
        $this->registry = $registry;
    }

    public function create(string $template, array $data = []): IMail
    {
        $from = $this->config->getValue('from');

        $mail = (new Mail(
            $this->latteFactory->createLatte(),
            $this->registry ?? $this->container,
            $this->config->getValue('template_dir'),
            (bool) $this->config->getValue('strict')
        ))
            ->setTemplate($template)
            ->setData($data)
            ->setFrom($from->email, $from->name);

        $replyTo = $this->config->getValue('reply_to');
        if ($replyTo !== null) {
            $mail->addReplyTo($replyTo->email, $replyTo->name);
        }

        $returnPath = $this->config->getValue('return_path');
        if ($returnPath !== null) {
            $mail->setReturnPath($returnPath);
        }

        $priority = $this->config->getValue('priority');
        if ($priority !== null) {
            $mail->setPriority($priority);
        }

        foreach ($this->config->getValue('headers') ?? [] as $name => $value) {
            $mail->addHeader($name, $value);
        }

        $embedDir = $this->config->getValue('embed_dir');
        if ($embedDir !== null) {
            $mail->setBasePath($embedDir);
        }

        foreach ($this->config->getValue('default_extensions') ?? [] as $extension) {
            $mail = $mail->extend($extension);
        }

        return $mail;
    }

    /**
     * Renders mail subject and bodies without sending
     */
    public function preview(IMail $mail): Preview
    {
        return Preview::fromMail($mail);
    }
}
