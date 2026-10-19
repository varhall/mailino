# Upgrade to 2.0

> Status: **planned**. This document describes the intended changes of mailino 2.0 so that 1.x users can prepare. Details may change until 2.0.0 is released. Version 1.x is maintained in the `v1.x` branch.

## Overview

In 1.x, the mail entity renders itself and extensions are decorators around it. In 2.0:

- `Mail` holds data only: template, data, recipients, attachments, headers and the list of extensions. It has no DI container and no Latte engine, so it can be serialized, for example to send it through a queue.
- `MailRenderer` renders the template and runs the extension pipeline. The result is `RenderedMail` (subject, HTML, plain text and metadata).
- Extensions transform `RenderedMail` instead of decorating `IMail`.

Common usage stays the same:

```php
$mail = $this->mailino->create('accounts/register', $data)
    ->extend(Prefix::class)
    ->extend(MjmlApi::class)
    ->setSubject('Welcome aboard')
    ->addTo($email);

$this->exchange->send($mail);
```

## Breaking changes

### Custom extensions

`MailDecorator` and `IExtension::extend()` are removed. An extension implements `process()` instead:

```php
// 1.x
class ReplyToFilter implements IExtension
{
    public function extend(IMail $mail): IMail
    {
        return new ReplyToFilterDecorator($mail);   // extends MailDecorator, overrides getReplyTo()
    }
}

// 2.0
class ReplyToFilter implements IExtension
{
    public function process(RenderedMail $mail, array $config): RenderedMail
    {
        $recipients = array_map(fn($to) => strtolower($to->getEmail()), $mail->getTo());

        return $mail->withReplyTo(array_filter(
            $mail->getReplyTo(),
            fn($replyTo) => !in_array(strtolower($replyTo->getEmail()), $recipients, true)
        ));
    }
}
```

Extensions must be registered as services, as in 1.3 (`ExtensionRegistry`).

### `extend()` no longer returns a new object

`extend()` adds the extension to the mail and returns the same instance, so the return value can't be lost by mistake. Code that checks decorator types (`instanceof PrefixDecorator`) must be removed.

### Rendering

`IMail::getHtmlBody()` and `getPlainBody()` are removed from the mail. Use `Mailino::preview($mail)` (available since 1.3) or `MailRenderer`.

### `IMail`

The wide `IMail` interface is replaced by the concrete `Mail` class (or a narrower interface). Custom `IMail` implementations must be rewritten.

### Exchanges

- Events receive the mail and the `RenderedMail`.
- New `QueueExchange` with an `IMailQueue` adapter interface (no dependency on a specific queue library) and a `MailWorker` that renders and sends queued mails.
- To send through a queue, template data must be serializable. Pass scalars, arrays or DTOs instead of ORM entities or objects with lazy loaded relations.

### Removed deprecated API

- `MailExchange::setVerifySsl()` / `isVerifySsl()`: configure `mail: context: ssl:` instead.
- URL as a path in `addFile()` / `Attachment::fromFile()`: use `Attachment::fromUrl()`.
- The DI container argument of `Mail` and `Mailino` constructors.

### Dependencies and code

- `qferr/mjml-php` is removed and replaced by own MJML interfaces. If you catch `Qferrer\Mjml\Exception\*`, catch the mailino exceptions instead.
- `declare(strict_types=1)` is used in all files.
- The minimum PHP version will follow the dependencies (probably 8.2).

## How to prepare in 1.3

1. Type your dependencies with `IExchange` instead of `MailExchange`.
2. Replace `getHtmlBody()` / `getPlainBody()` calls on mail objects with `Mailino::preview()`.
3. Always use the return value of `extend()`. That code works in both versions.
4. Keep decorators small, ideally overriding a single method, so they are easy to rewrite.
5. If you plan to use a queue, start passing serializable data to templates.
