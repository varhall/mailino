# Mailino

- [Installation](#installation)
- [Setup](#setup)
- [Configuration Reference](#configuration-reference)
- [Basic usage](#basic-usage)
- [How it works](#how-it-works)
- [Templates](#templates)
  - [Subject](#subject)
  - [Plain text](#plain-text)
  - [Embedded images](#embedded-images)
  - [Latte engine](#latte-engine)
  - [Strict mode](#strict-mode)
- [Attachments](#attachments)
- [Extensions](#extensions)
  - [Prefix](#prefix)
  - [MJML](#mjml)
  - [Default extensions](#default-extensions)
  - [Custom extension](#custom-extension)
- [Preview](#preview)
- [Exchanges](#exchanges)
- [Queue](#queue)
- [Events](#events)
- [SSL verification](#ssl-verification)

Mailino is Nette mailing extension to create modular structure of mail message and mailing exchanges.

> {tip} Upgrading from 1.x? See [UPGRADE-2.0.md](../UPGRADE-2.0.md). Version 1.x is maintained in the `v1.x` branch.

> {tip} Before getting started, be sure to have working Nette project and properly configured a mailing in your application's `config/local.neon` configuration file. For more information on configuring mailing, check out [the mailing documentation](https://doc.nette.org/en/mail).

<a name="installation"></a>
## Installation

    composer require varhall/mailino

<a name="setup"></a>
## Setup

Configure mail server settings and enable Mailino extension. The configuration contains sample configuration of possible extensions.

    extensions:
	    mailino: Varhall\Mailino\DI\MailinoExtension

    mailino:
        template_dir: %appDir%/templates/emails/

        # Required: default sender applied to every email
        from:
            email: 'sender@test.com'
            name: 'Sender'

        # Optional: global Reply-To address (e.g. a no-reply inbox)
        reply_to:
            email: 'noreply@test.com'
            name: 'No Reply'

        # Optional: envelope Return-Path for bounces
        return_path: 'bounce@test.com'

        # Optional: X-Priority header (1=High, 3=Normal, 5=Low)
        priority: 3

        # Optional: custom headers added to every email
        headers:
            X-Mailer-Tag: 'registration'

        # Optional: directory with images embedded into HTML body (<img src="logo.png">)
        embed_dir: %appDir%/templates/emails/images

        # Optional: cache directory for compiled templates
        temp_dir: %tempDir%/cache/mailino

        # Optional: throw exception on template warnings (e.g. undefined variable)
        strict: %debugMode%

        # Optional: extensions applied to every mail created by Mailino::create()
        default_extensions:
            - Varhall\Mailino\Extensions\Prefix

        extensions:
            prefix:
                subject: 'TST'
    
            mjml.api:
                id: 'abcdef-1234-5678-ghijkl'
                secret: 'ghijkl-5678-1234-abcdef'
    
            mjml.binary:
                binary: '%appDir%/../node_modules/.bin/mjml'

<a name="configuration-reference"></a>
## Configuration Reference

| Key | Required | Type | Description |
|-----|----------|------|-------------|
| `template_dir` | yes | string | Base directory for Latte template files |
| `from.email` | yes | string | Default sender email |
| `from.name` | no | string | Default sender display name |
| `reply_to.email` | no | string | Global Reply-To address (e.g. a no-reply inbox) |
| `reply_to.name` | no | string | Global Reply-To display name |
| `return_path` | no | string | Envelope Return-Path for bounce handling |
| `priority` | no | int | X-Priority, 1–5 (1 = High, 3 = Normal, 5 = Low) |
| `headers` | no | array | Custom headers added to every email (key: value) |
| `embed_dir` | no | string | Directory of local images embedded into HTML body as inline attachments |
| `temp_dir` | no | string | Cache directory for compiled templates (default Latte factory only) |
| `strict` | no | bool | Convert template warnings to exceptions, default `false` |
| `default_extensions` | no | list | Extension classes applied to every mail created by `Mailino::create()` |
| `extensions` | no | array | Extension configuration (`prefix`, `mjml.api`, `mjml.binary`, custom) |

Configuration of built-in extensions is validated when DI container is compiled, so typo or missing required key is reported immediately. Custom extension sections are passed as they are.

<a name="basic-usage"></a>
## Basic Usage

Create mail using Mailino factory - sender and other defaults from configuration are already set. Template path is relative to `template_dir`, without extension. Mailino looks for:

- %appDir%/templates/emails/accounts/register.html.latte
- %appDir%/templates/emails/accounts/register.plain.latte (optional)

```php
namespace App\Services;

use Varhall\Mailino\Exchanges\IExchange;
use Varhall\Mailino\Mailino;

class EmailsService
{
    public function __construct(
        private Mailino $mailino,
        private IExchange $exchange,
    ) {}

    public function sendRegistration(string $email): void
    {
        $mail = $this->mailino->create('accounts/register', [ 'email' => $email ])
            ->setSubject('Welcome aboard')
            ->addTo($email);

        $this->exchange->send($mail);
    }
}
```

Global defaults (`reply_to`, `return_path`, `priority`, `headers`, `embed_dir`, `default_extensions`) can be overridden on any mail:

```php
$mail = $this->mailino->create('accounts/register', $data)
    ->setReturnPath('custom-bounce@test.com')   // overrides global
    ->addReplyTo('support@company.com')         // appends to global
    ->setPriority(1);                           // overrides global
```

Recipients, attachments, headers and extensions can be removed using `clearTo()`, `clearCc()`, `clearBcc()`, `clearReplyTo()`, `clearFiles()`, `removeHeader($name)` and `removeExtension($class)`.

Mail must have sender and at least one recipient (`to`, `cc` or `bcc`), otherwise `Nette\InvalidStateException` is thrown when sending.

<a name="how-it-works"></a>
## How it works

`Mail` holds data only (template, data, recipients, attachments, headers and extensions). It has no DI container or template engine, so it can be serialized and sent through a queue.

When mail is sent (or previewed), `MailRenderer`:

1. renders `*.html.latte` and optional `*.plain.latte` template,
2. resolves subject,
3. applies extensions in order they were added to the mail,
4. generates plain text from final HTML when there is no plain template.

The result is immutable `RenderedMail`, which is converted to `Nette\Mail\Message` by `MessageFactory` and delivered by exchange.

<a name="templates"></a>
## Templates

<a name="subject"></a>
### Subject

Subject is resolved in this order:

1. subject set by `setSubject()`,
2. `{define subject}` block of HTML template (not rendered into the body),
3. `<title>` or `<mj-title>` of HTML template.

```latte
{define subject}Welcome aboard, {$name}{/define}

<p>Hello {$name}</p>
```

<a name="plain-text"></a>
### Plain text

Plain text template `*.plain.latte` is optional. When it does not exist, plain text alternative is generated from the final HTML body. Plain text template is rendered as text, so values are not HTML-escaped.

<a name="embedded-images"></a>
### Embedded images

When `embed_dir` is configured (or `setBasePath($dir)` is called on mail), local images referenced in HTML body are attached as inline attachments, so they are displayed even when email client blocks remote images.

```latte
<img src="logo.png">     {* embed_dir/logo.png is attached and src rewritten to cid: *}
```

<a name="latte-engine"></a>
### Latte engine

By default, plain `Latte\Engine` is used for mail templates. To share configuration of application templates (translator, filters, Latte extensions), use application Latte factory from `nette/application`:

    services:
        mailino.template_factory: Varhall\Mailino\NetteLatteEngineFactory

Any other implementation of `Varhall\Mailino\ILatteEngineFactory` can be registered the same way.

<a name="strict-mode"></a>
### Strict mode

By default, missing template variable produces just PHP warning and email is sent with empty value. With `strict: true` the warning is converted to `ErrorException` and email is not sent. It is recommended to enable strict mode at least in development (`strict: %debugMode%`).

<a name="attachments"></a>
## Attachments

```php
use Varhall\Mailino\Entities\Attachment;

$mail->addFile('/path/to/file.pdf');                              // local file path
$mail->addFile(new \SplFileInfo('/path/to/file.pdf'), 'name.pdf'); // local file
$mail->addFile($fileUpload);                                       // Nette\Http\FileUpload
$mail->addFile(Attachment::fromContent($pdf, 'invoice.pdf'));      // content in memory, type detected
$mail->addFile(Attachment::fromUrl('https://example.com/a.pdf'));  // downloaded file
```

> {warning} Never pass user input as path or URL. It allows attaching arbitrary local files or requesting internal URLs (SSRF). URLs are accepted only by `Attachment::fromUrl()`.

<a name="extensions"></a>
## Extensions

Extensions transform rendered mail - subject, bodies, recipients or headers. They are added by `extend()` and applied in order they were added. Adding the same extension again has no effect.

```php
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\Prefix;

$mail = $this->mailino->create('template', $data)
    ->extend(MjmlApi::class)
    ->extend(Prefix::class)
    ->setSubject('Welcome aboard');
```

<a name="prefix"></a>
### Prefix

Prefix extension adds prefix to subject so the emails can be effectively sorted in email clients. Configuration is done through `prefix` section.

    mailino:
        extensions:
            prefix:
                subject: 'TST'

Mail extended by `Prefix::class` will have subject `[TST] Welcome aboard`. Prefix is added only once.

<a name="mjml"></a>
### MJML

[MJML](https://mjml.io) is a markup language designed to reduce the pain of coding a responsive email. MJML is written in `template.html.latte` and all Latte features can be used. To compile MJML you can use external API service (`MjmlApi`) or local binary (`MjmlBinary`).

#### API

    mailino:
        extensions:
            mjml.api:
                id: 'my-app'
                secret: 'my-secret'
                url: 'https://mjml.example.com/v1'     # optional, requests are sent to {url}/render
                timeout: 10
                validation_level: strict

| Key | Required | Default | Description |
|-----|----------|---------|-------------|
| `id` | yes | | Application ID (Basic auth username) |
| `secret` | yes | | Secret key (Basic auth password) |
| `url` | no | `https://api.mjml.io/v1` | Base URL of MJML API, any service with the same contract as mjml.io can be used |
| `timeout` | no | `30` | Total request timeout in seconds |
| `connect_timeout` | no | `10` | Connection timeout in seconds |
| `validation_level` | no | `soft` | `strict` throws `MjmlException` when API reports MJML validation errors |

MJML API requires `curl` PHP extension. Credentials for the official API can be requested on https://mjml.io/api.

#### Binary

    mailino:
        extensions:
            mjml.binary:
                binary: [ node, '%appDir%/../node_modules/mjml/bin/mjml' ]
                timeout: 10

Binary can be a path (string, executed by shell, so e.g. `npx mjml` works) or a list of command and arguments (executed directly without shell). The process is killed after `timeout` seconds (default 30, 0 = no limit). Local MJML binary requires Node:

    npm install mjml

Both extensions throw `Varhall\Mailino\Exceptions\MjmlException` when rendering fails.

<a name="default-extensions"></a>
### Default extensions

Extensions listed in `default_extensions` are added to every mail created by `Mailino::create()`, e.g. subject prefix on staging server.

    mailino:
        default_extensions:
            - Varhall\Mailino\Extensions\Prefix

<a name="custom-extension"></a>
### Custom extension

Extension implements `IExtension` and returns modified copy of `RenderedMail`:

```php
use Varhall\Mailino\Config;
use Varhall\Mailino\Extensions\IExtension;
use Varhall\Mailino\Rendering\RenderedMail;

class Footer implements IExtension
{
    public function __construct(private Config $config) {}

    public function process(RenderedMail $mail): RenderedMail
    {
        $text = $this->config->getExtension('footer')['text'];

        return $mail->withHtmlBody($mail->getHtmlBody() . "<p>{$text}</p>");
    }
}
```

Register it as a service, all services implementing `IExtension` are registered into `ExtensionRegistry` automatically. Its configuration is placed in `extensions` section:

    services:
        - App\Mail\Footer

    mailino:
        extensions:
            footer:
                text: 'Sent by Mailino'

<a name="preview"></a>
## Preview

Mail can be rendered without sending, e.g. for preview in administration:

```php
$rendered = $this->mailino->preview($mail);   // RenderedMail

$rendered->getSubject();
$rendered->getHtmlBody();
$rendered->getPlainBody();
```

<a name="exchanges"></a>
## Exchanges

Exchange sends the mail. All exchanges implement `Varhall\Mailino\Exchanges\IExchange`.

| Exchange | Description |
|----------|-------------|
| `MailExchange` | Sends mail using Nette `Mailer` (default, service `mailino.exchange`) |
| `QueueExchange` | Pushes mail into queue, see [Queue](#queue) |
| `LogExchange` | Writes mails as `.eml` files into directory instead of sending (development, staging) |
| `NullExchange` | Sends nothing, keeps messages in memory, `getMessages()` (tests) |

Type your dependencies with `IExchange` and replace the default service in configuration, e.g. for development:

    services:
        mailino.exchange: Varhall\Mailino\Exchanges\LogExchange(%tempDir%/mails)

<a name="queue"></a>
## Queue

Sending through a queue moves SMTP communication and MJML rendering out of the web request. Mailino does not depend on any queue library. Implement `IMailQueue` adapter for your queue (Symfony Messenger, RabbitMQ, database table...) and call `QueueWorker` in consumer:

```php
use Varhall\Mailino\Queue\IMailQueue;
use Varhall\Mailino\Queue\QueueWorker;

class MailQueue implements IMailQueue
{
    public function push(string $payload): void
    {
        // store payload (JSON string) into your queue
    }
}

// queue consumer
$worker->handle($payload);   // QueueWorker - renders and sends mail by real exchange
```

    services:
        - App\Mail\MailQueue
        mailino.exchange: Varhall\Mailino\Queue\QueueExchange
        - Varhall\Mailino\Queue\QueueWorker(Varhall\Mailino\Exchanges\MailExchange())

> {warning} Mail is serialized to JSON, so template data must contain scalars, `null` and arrays only. Pass IDs or arrays instead of entities - objects throw `InvalidArgumentException` when the mail is queued.

`QueueExchange` triggers `onQueued` event. Rendering errors appear in the worker, which uses exchange events described below.

<a name="events"></a>
## Events

Exchanges extending `Exchange` (`MailExchange`, `LogExchange`, `NullExchange`) trigger events:

| Event | Parameters | Description |
|-------|------------|-------------|
| `onSending` | `Mail $mail` | Before rendering |
| `onSuccess` | `Mail $mail, RenderedMail $rendered` | After successful delivery |
| `onError` | `Mail $mail, \Throwable $exception` | Rendering or delivery failed, exception is re-thrown |

```php
$this->exchange->onSuccess[] = function (Mail $mail, RenderedMail $rendered) {
    $this->logger->log('Sent email ' . $rendered->getSubject());
};

$this->exchange->onError[] = function (Mail $mail, \Throwable $exception) {
    $this->logger->log('Sending email ' . $mail->getTemplate() . ' failed: ' . $exception->getMessage());
};
```

<a name="ssl-verification"></a>
## SSL verification

Mailino does not change SSL settings. If SMTP server uses self-signed certificate (e.g. in development), disable verification for SMTP connection only in Nette mail configuration:

    mail:
        smtp: true
        host: localhost
        context:
            ssl:
                verify_peer: false
                verify_peer_name: false
                allow_self_signed: true
