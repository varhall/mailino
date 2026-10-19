<?php

declare(strict_types=1);

namespace Varhall\Mailino\Rendering;

use Latte\ContentType;
use Latte\Engine;
use Nette\Mail\Message;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Extensions\ExtensionRegistry;
use Varhall\Mailino\ILatteEngineFactory;

/**
 * Renders mail templates and applies mail extensions
 *
 * 1. renders HTML template and optional plain text template
 * 2. resolves subject - explicit, {define subject} block, <title> or <mj-title> of HTML template
 * 3. applies extensions in order they were added to the mail
 * 4. generates plain text from final HTML when there is no plain text template
 */
class MailRenderer
{
    public const SUBJECT_BLOCK = 'subject';

    public function __construct(
        private readonly string $templateDir,
        private readonly ILatteEngineFactory $latteFactory,
        private readonly ExtensionRegistry $extensions,
        private readonly bool $strict = false
    ) {
    }

    public function render(Mail $mail): RenderedMail
    {
        $latte = $this->latteFactory->createLatte();

        $html = $this->renderTemplate($latte, $mail, 'html', ContentType::Html);
        $plain = is_file($this->templatePath($mail, 'plain'))
            ? $this->renderTemplate($latte, $mail, 'plain', ContentType::Text)
            : '';

        $rendered = RenderedMail::fromMail($mail, $this->resolveSubject($latte, $mail, $html), $html, $plain);

        foreach ($mail->getExtensions() as $class) {
            $rendered = $this->extensions->get($class)->process($rendered);
        }

        if ($rendered->getPlainBody() === '' && $rendered->getHtmlBody() !== '') {
            $rendered = $rendered->withPlainBody(self::htmlToText($rendered->getHtmlBody()));
        }

        return $rendered;
    }

    public static function htmlToText(string $html): string
    {
        // the same conversion Nette uses when plain body of message is empty
        return (new Message())->setHtmlBody($html)->getBody();
    }


    /// PRIVATE METHODS

    private function templatePath(Mail $mail, string $type): string
    {
        return implode(DIRECTORY_SEPARATOR, [ rtrim($this->templateDir, '/\\'), "{$mail->getTemplate()}.{$type}.latte" ]);
    }

    private function resolveSubject(Engine $latte, Mail $mail, string $html): string
    {
        if ($mail->getSubject() !== null) {
            return $mail->getSubject();
        }

        $latte->setContentType(ContentType::Html);
        $template = $latte->createTemplate($this->templatePath($mail, 'html'), $mail->getData());

        if ($template->hasBlock(self::SUBJECT_BLOCK)) {
            return self::normalizeText($this->renderTemplate($latte, $mail, 'html', ContentType::Html, self::SUBJECT_BLOCK));
        }

        if (preg_match('#<(mj-)?title>(.+?)</\1?title>#is', $html, $match)) {
            return self::normalizeText($match[2]);
        }

        return '';
    }

    private function renderTemplate(Engine $latte, Mail $mail, string $type, string $contentType, ?string $block = null): string
    {
        $latte->setContentType($contentType);
        $path = $this->templatePath($mail, $type);

        if (!$this->strict) {
            return $latte->renderToString($path, $mail->getData(), $block);
        }

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

        try {
            return $latte->renderToString($path, $mail->getData(), $block);
        } finally {
            restore_error_handler();
        }
    }

    private static function normalizeText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('#\s+#u', ' ', $text));
    }
}
