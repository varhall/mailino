<?php

declare(strict_types=1);

namespace Varhall\Mailino\Exchanges;

use Nette\IOException;
use Nette\Utils\FileSystem;
use Varhall\Mailino\Rendering\MailRenderer;
use Varhall\Mailino\Rendering\RenderedMail;

/**
 * Writes messages as .eml files into directory instead of sending (development, staging)
 */
class LogExchange extends Exchange
{
    private string $directory;

    private MessageFactory $messageFactory;

    public function __construct(string $directory, MailRenderer $renderer, ?MessageFactory $messageFactory = null)
    {
        parent::__construct($renderer);
        $this->directory = rtrim($directory, '/\\');
        $this->messageFactory = $messageFactory ?? new MessageFactory();
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    protected function deliver(RenderedMail $mail): void
    {
        FileSystem::createDir($this->directory);

        $file = sprintf('%s/%s-%s.eml', $this->directory, date('Y-m-d-His'), bin2hex(random_bytes(4)));

        if (@file_put_contents($file, $this->messageFactory->create($mail)->generateMessage()) === false) {
            throw new IOException("Unable to write mail to '{$file}'");
        }
    }
}
