<?php

declare(strict_types=1);

namespace Varhall\Mailino\Queue;

use Nette\InvalidArgumentException;
use Varhall\Mailino\Entities\Attachment;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Entities\Recipient;

/**
 * Serializes mail to JSON (no PHP unserialize - payload from queue can't instantiate arbitrary classes).
 * Template data must contain scalars, null and arrays only.
 */
class MailSerializer
{
    private const VERSION = 1;

    public function serialize(Mail $mail): string
    {
        $this->assertSerializable($mail->getData(), 'data');

        return json_encode([
            'version'       => self::VERSION,
            'template'      => $mail->getTemplate(),
            'data'          => $mail->getData(),
            'subject'       => $mail->getSubject(),
            'from'          => $mail->getFrom() !== null ? self::recipient($mail->getFrom()) : null,
            'to'            => array_map([ self::class, 'recipient' ], $mail->getTo()),
            'cc'            => array_map([ self::class, 'recipient' ], $mail->getCc()),
            'bcc'           => array_map([ self::class, 'recipient' ], $mail->getBcc()),
            'replyTo'       => array_map([ self::class, 'recipient' ], $mail->getReplyTo()),
            'files'         => array_map(fn(Attachment $file) => [
                'name'          => $file->getName(),
                'contentType'   => $file->getContentType(),
                'content'       => base64_encode($file->getContent()),
            ], $mail->getFiles()),
            'returnPath'    => $mail->getReturnPath(),
            'priority'      => $mail->getPriority(),
            'headers'       => $mail->getHeaders(),
            'basePath'      => $mail->getBasePath(),
            'extensions'    => $mail->getExtensions(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    public function unserialize(string $payload): Mail
    {
        $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || ($data['version'] ?? null) !== self::VERSION) {
            throw new InvalidArgumentException('Invalid or unsupported mail payload');
        }

        $mail = (new Mail($data['template'], $data['data']))
            ->setSubject($data['subject'])
            ->setReturnPath($data['returnPath'])
            ->setPriority($data['priority'])
            ->setBasePath($data['basePath']);

        if ($data['from'] !== null) {
            $mail->setFrom($data['from']['email'], $data['from']['name']);
        }

        foreach ([ 'to' => 'addTo', 'cc' => 'addCc', 'bcc' => 'addBcc', 'replyTo' => 'addReplyTo' ] as $key => $method) {
            foreach ($data[$key] as $recipient) {
                $mail->$method($recipient['email'], $recipient['name']);
            }
        }

        foreach ($data['files'] as $file) {
            $content = base64_decode($file['content'], true);

            if ($content === false) {
                throw new InvalidArgumentException("Invalid content of attachment '{$file['name']}'");
            }

            $mail->addFile(new Attachment($content, $file['contentType'], $file['name']));
        }

        foreach ($data['headers'] as $name => $value) {
            $mail->addHeader($name, $value);
        }

        foreach ($data['extensions'] as $extension) {
            $mail->extend($extension);
        }

        return $mail;
    }

    /**
     * @return array{email: string, name: ?string}
     */
    private static function recipient(Recipient $recipient): array
    {
        return [ 'email' => $recipient->getEmail(), 'name' => $recipient->getName() ];
    }

    private function assertSerializable(mixed $value, string $path): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->assertSerializable($item, "{$path}.{$key}");
            }

            return;
        }

        if ($value !== null && !is_scalar($value)) {
            throw new InvalidArgumentException(sprintf(
                "Template %s of type %s can't be sent through queue, pass scalars or arrays instead of objects",
                $path,
                get_debug_type($value)
            ));
        }
    }
}
