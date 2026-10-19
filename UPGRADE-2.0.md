# Upgrade to 2.0

Version 1.x is maintained in the `v1.x` branch (bug fixes only).

## Overview

In 1.x, the mail entity rendered itself and extensions were decorators around it. In 2.0:

- `Mail` holds data only: template, data, recipients, attachments, headers and the list of extensions. It has no DI container and no Latte engine, so it can be serialized and sent through a queue.
- `MailRenderer` renders the template and runs the extension pipeline. The result is the immutable `RenderedMail` (subject, HTML, plain text, recipients, headers).
- Extensions transform `RenderedMail` instead of decorating the mail.

Common usage stays the same:

```php
$mail = $this->mailino->create('accounts/register', $data)
    ->extend(Prefix::class)
    ->extend(MjmlApi::class)
    ->setSubject('Welcome aboard')
    ->addTo($email);

$this->exchange->send($mail);
```

## Requirements

- PHP 8.2 or newer.
- The `qferr/mjml-php` dependency is removed. `ext-curl` is needed only for the `MjmlApi` extension.

## Breaking changes

### `IMail` → `Mail`

The `IMail` interface is removed. Use the concrete class `Varhall\Mailino\Entities\Mail`, which `Mailino::create()` returns. Type hints `IMail` → `Mail`.

- `new Mail($template, $data)`: the constructor takes the template and data. The Latte engine, DI container and template directory are gone.
- Setters return `static` (the same instance).
- `getSubject()` returns `?string`. `null` means the subject is rendered from the template.
- `getFrom()` returns `?Recipient`.
- `getHtmlBody()` / `getPlainBody()` are removed from the mail. Use `Mailino::preview($mail)`, which returns `RenderedMail`.
- `getExtensionFactory()` is removed. New methods are `getExtensions()`, `hasExtension()`, `removeExtension()` and `clearFiles()`.
- `Recipient` and `Attachment` are immutable. Their setters are removed, and `Attachment::withName()` was added.

### `extend()` no longer returns a new object

`extend()` adds the extension class to the mail and returns the same instance. Extensions run when the mail is rendered, in the order they were added. Adding the same extension again has no effect.

```php
// 1.x - the result of extend() had to be used, otherwise the extension was lost
$mail = $mail->extend(Prefix::class);

// 2.0 - both work
$mail->extend(Prefix::class);
```

Checks of decorator types (`instanceof PrefixDecorator`) must be removed. Use `$mail->hasExtension(Prefix::class)` instead.

### Custom extensions

`MailDecorator`, all decorators and `IExtension::extend()` are removed. An extension implements `process()`, receives `RenderedMail` and returns its modified copy (`with*()` methods):

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
use Varhall\Mailino\Entities\Recipient;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\Rendering\RenderedMail;

class ReplyToFilter implements IExtension
{
    public function process(RenderedMail $mail): RenderedMail
    {
        $recipients = array_map(fn(Recipient $to) => strtolower($to->getEmail()), $mail->getTo());

        return $mail->withReplyTo(array_filter(
            $mail->getReplyTo(),
            fn(Recipient $replyTo) => !in_array(strtolower($replyTo->getEmail()), $recipients, true)
        ));
    }
}
```

The extension must be registered as a service, as in 1.3. All `IExtension` services are added to `ExtensionRegistry` automatically. If the extension needs configuration, inject `Varhall\Mailino\Config` and read `getExtension('name')`.

### Exchanges

- `IExchange::send(Mail $mail)`. `Exchange` subclasses implement `deliver(RenderedMail $mail)` instead of `send()`.
- Exchange constructors take `MailRenderer`: `new MailExchange($mailer, $renderer)`. In DI they are autowired.
- `MailExchange::createMessage()` is removed. Use `MessageFactory::create(RenderedMail)`.
- `MailExchange::setVerifySsl()` / `isVerifySsl()` are removed. Configure `mail: context: ssl:` instead.
- Events:
  - `onSending(Mail $mail)`
  - `onSuccess(Mail $mail, RenderedMail $rendered)`
  - `onError(Mail $mail, \Throwable $exception)`

### Subject from template

In addition to `{define subject}` and `<title>`, the subject is also read from `<mj-title>` of MJML templates. The subject is resolved before extensions run, so `Prefix` is applied to subjects taken from the template too.

### Attachments

- A URL as a path (`addFile('https://...')`, `Attachment::fromPath()`, `Attachment::fromFile()`) throws `InvalidArgumentException`. Use `Attachment::fromUrl()`.
- The `Attachment` constructor requires a name: `new Attachment($content, $contentType, $name)`.

### MJML

- `Qferrer\Mjml\*` classes and exceptions are replaced by `Varhall\Mailino\Extensions\Mjml\IMjmlRenderer`, `MjmlApiRenderer` and `MjmlBinaryRenderer`. All errors throw `Varhall\Mailino\Exceptions\MjmlException`.
- `MjmlHttpApi` was renamed to `MjmlApiRenderer`, and its `getHtml()` method to `render()`.
- `MjmlDecorator` is removed. `MjmlApi` / `MjmlBinary` extensions transform the HTML body. There is no longer any need to cache the rendered HTML, because mail is rendered once per send.

### Other

- `Preview` is removed. `Mailino::preview()` returns `RenderedMail`.
- `Config` accepts `\stdClass` only.
- `declare(strict_types=1)` is used in all files.

## New in 2.0

- Sending through a queue: `QueueExchange`, the `IMailQueue` adapter interface, `QueueWorker` and `MailSerializer`. The payload is JSON, so template data must contain scalars, `null` and arrays only. See [documentation](.docs/README.md#queue).
- `RenderedMail` with `with*()` methods for extensions.
- `MailRenderer` as a standalone service, for example for rendering without Mailino.

## Upgrade checklist

1. Replace `IMail` type hints with `Mail`.
2. Rewrite custom extensions to `process(RenderedMail $mail): RenderedMail` and remove decorators.
3. Replace `$mail->getHtmlBody()` / `getPlainBody()` with `$mailino->preview($mail)`.
4. Update exchange event handlers (`onSuccess` gets `RenderedMail`, `onError` gets `\Throwable`).
5. Replace URL attachments with `Attachment::fromUrl()`.
6. Catch `Varhall\Mailino\Exceptions\MjmlException` instead of `Qferrer\Mjml\Exception\*`.
7. If you use a queue, pass serializable data (scalars, arrays) to templates.
