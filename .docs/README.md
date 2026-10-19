# Mailino

- [Installation](#installation)
- [Setup](#setup)
- [Configuration Reference](#configuration-reference)
- [Basic usage](#basic-usage)
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
- [Events](#events)
- [SSL verification](#ssl-verification)

Mailino is Nette mailing extension to create modular structure of mail message and mailing exchanges.

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
                url: 'https://api.mjml.io/v1'     # optional, custom MJML API endpoint
    
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
| `extensions` | no | array | Extension-specific configuration (`prefix`, `mjml.api`, `mjml.binary`, custom) |

Configuration of built-in extensions is validated when DI container is compiled, so typo or missing required key is reported immediately. Custom extension sections are passed as they are.

Global defaults (`reply_to`, `return_path`, `priority`, `headers`, `embed_dir`) are applied automatically by `Mailino::create()` but can be overridden on any individual mail object:

    $mail = $this->mailino->create('accounts/register', $data)
        ->setReturnPath('custom-bounce@test.com')   // overrides global
        ->addReplyTo('support@company.com')         // appends to global
        ->setPriority(1);                           // overrides global

Recipients and headers can be removed again using `clearTo()`, `clearCc()`, `clearBcc()`, `clearReplyTo()` and `removeHeader($name)`.

<a name="basic-usage"></a>
## Basic Usage
Create an email object using Mailino factory first. This object is automatically configured from extension config. Sender is already defined. Then specify template file relative from `template_dir`. Template files are without extensions. Mailino will look for:

- %appDir%/templates/emails/accounts/register.html.latte
- %appDir%/templates/emails/accounts/register.plain.latte (optional)

This very simple example shows, how to define mail service and send email using Mailino.

    <?php

    namespace App\Services;

    use Varhall\Mailino\Mailino;
    use Varhall\Mailino\Exchanges\MailExchange;

    class EmailsService
    {
        public function __construct(
            private Mailino $mailino,
            private MailExchange $exchange,
        ) {}

        public function send()
        {
            $mail = $this->mailino->create('accounts/register', [ 'email' => 'karl.hermann@gmail.com', 'password' => '123456Ab+' ])
                        ->setSubject('Welcome aboard')
                        ->addTo('karl.hermann@gmail.com');

            $this->exchange->send($mail);
        }
    }

Mail must have sender and at least one recipient (`to`, `cc` or `bcc`), otherwise `Nette\InvalidStateException` is thrown when sending.

If you need Nette `Message` object without sending it (e.g. to store it or to send it by other mailer), use `MailExchange::createMessage($mail)`.

<a name="templates"></a>
## Templates

<a name="subject"></a>
### Subject

Subject can be defined directly in HTML template using `{define subject}` block. The block is not rendered into the body. Subject set by `setSubject()` always takes precedence.

    {define subject}Welcome aboard, {$name}{/define}

    <p>Hello {$name}</p>

If there is no subject at all, `<title>` of HTML body is used (e.g. `<mj-title>` in MJML templates).

<a name="plain-text"></a>
### Plain text

Plain text template `*.plain.latte` is optional. When it does not exist, plain text alternative is generated from HTML body automatically. Plain text template is rendered as text, so values are not HTML-escaped.

<a name="embedded-images"></a>
### Embedded images

When `embed_dir` is configured (or `setBasePath($dir)` is called on mail), local images referenced in HTML body are attached as inline attachments, so they are displayed even when email client blocks remote images.

    <img src="logo.png">     {* embed_dir/logo.png is attached and src rewritten to cid: *}

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

Files are attached using `addFile($file, $name = null)` method. Supported values are:

    use Varhall\Mailino\Entities\Attachment;

    $mail->addFile('/path/to/file.pdf');                              // local file path
    $mail->addFile(new \SplFileInfo('/path/to/file.pdf'), 'name.pdf'); // local file
    $mail->addFile($fileUpload);                                       // Nette\Http\FileUpload
    $mail->addFile(Attachment::fromContent($pdf, 'invoice.pdf'));      // content in memory, type detected
    $mail->addFile(Attachment::fromUrl('https://example.com/a.pdf'));  // downloaded file

> {warning} Never pass user input as path or URL. It allows attaching arbitrary local files or requesting internal URLs (SSRF). Passing URL as path is deprecated, use `Attachment::fromUrl()` explicitly.

<a name="extensions"></a>
## Extensions

Mailino deliveres some extensions by default. These are used to extend `IMail` class. Mail is extended simply by calling `extend` method. These extensions can be arbitrarily combined, like shows the example below.

    <?php

    use Varhall\Mailino\Extensions\Prefix;
    use Varhall\Mailino\Extensions\MjmlApi;

    $mail = $this->mailino->create('template', $data)
                        ->extend(Prefix::class)
                        ->extend(MjmlApi::class)
                        ->setSubject('Welcome aboard');        

> {warning} `extend()` returns **new** decorated object. Always use the returned value, otherwise the extension is not applied:
>
>     $mail->extend(Prefix::class);             // wrong - result is lost
>     $mail = $mail->extend(Prefix::class);     // correct

<a name="prefix"></a>
### Prefix

Prefix extension adds prefix to subject so the emails are virtually subscribed and can be effectively sorted in email clients.

Configuration is done through `prefix` section in Mailino configuration.

    mailino:
        extensions:
            prefix:
                subject: 'TST'

Extension is then used by:

    <?php
    
    use Varhall\Mailino\Extensions\Prefix;

    $mail = $this->mailino->create('template', $data)
                        ->extend(Prefix::class)
                        ->setSubject('Welcome aboard');

Current email will have subject `[TST] Welcome aboard`. Applying the extension more than once adds the prefix only once.

<a name="mjml"></a>
### MJML

[MJML](https://mjml.io) is a markup language designed to reduce the pain of coding a responsive email. Its semantic syntax makes it easy and straightforward and its rich standard components library speeds up your development time and lightens your email codebase. MJML’s open-source engine generates high quality responsive HTML compliant with best practices.

Mailino provides extension to simply use MJML in email templates. To compile MJML template you can use external API service or local binary service.

There are two versions of MJML extension - API and Binary. Both provide the same functions, only their implementation differ. 

MJML is written in `template.html.latte` and all `Latte` features can be used of course. Rendered HTML is cached per mail object, so calling `getHtmlBody()` repeatedly (e.g. in `onSending` event) does not call MJML again.

#### API

MJML API extension requires API credentials to be configured in `mjml.api` section in Mailino configuration.

    mailino:
        extensions:
            mjml.api:
                id: 'abcdef-1234-5678-ghijkl'
                secret: 'ghijkl-5678-1234-abcdef'

Extension is then used by:

    <?php
    
    use Varhall\Mailino\Extensions\MjmlApi;

    $mail = $this->mailino->create('template', $data)
                        ->extend(MjmlApi::class)
                        ->setSubject('Welcome aboard');

MJML credentials can be requested on https://mjml.io/api. 

By default the official endpoint `https://api.mjml.io/v1` is used. Optional `url` option allows to use any custom (e.g. self-hosted) service with exactly the same contract as the official MJML API. The value is the base URL, `/render` is appended automatically and requests are authenticated using HTTP Basic auth with `id` and `secret`.

    mailino:
        extensions:
            mjml.api:
                id: 'my-app'
                secret: 'my-secret'
                url: 'https://mjml.example.com/v1'     # requests are sent to https://mjml.example.com/v1/render
                timeout: 10
                validation_level: strict

| Key | Required | Default | Description |
|-----|----------|---------|-------------|
| `id` | yes | | Application ID (Basic auth username) |
| `secret` | yes | | Secret key (Basic auth password) |
| `url` | no | `https://api.mjml.io/v1` | Base URL of MJML API |
| `timeout` | no | `30` | Total request timeout in seconds |
| `connect_timeout` | no | `10` | Connection timeout in seconds |
| `validation_level` | no | `soft` | `strict` throws `ApiException` when API reports MJML validation errors, `soft` ignores them |

#### Binary

MJML Binary extension requires path to MJML CLI to be configured in `mjml.binary` section in Mailino configuration.

    mailino:
        extensions:
            mjml.binary:
                binary: '%appDir%/../node_modules/.bin/mjml'

Extension is then used by:

    <?php
    
    use Varhall\Mailino\Extensions\MjmlBinary;

    $mail = $this->mailino->create('template', $data)
                        ->extend(MjmlBinary::class)
                        ->setSubject('Welcome aboard');

Local MJML binary requires Node. Installation can be simply done using

    npm install mjml

<a name="default-extensions"></a>
### Default extensions

Extensions listed in `default_extensions` are applied to every mail created by `Mailino::create()`. It is useful e.g. for subject prefix on staging server.

    mailino:
        default_extensions:
            - Varhall\Mailino\Extensions\Prefix

<a name="custom-extension"></a>
### Custom extension

Extension consists of factory implementing `IExtension` and decorator extending `MailDecorator`. Decorator overrides only methods it changes, all other calls are delegated to decorated mail.

    use Varhall\Mailino\Config;
    use Varhall\Mailino\Entities\IMail;
    use Varhall\Mailino\Extensions\Decorators\MailDecorator;
    use Varhall\Mailino\Extensions\IExtension;

    class Footer implements IExtension
    {
        public function __construct(private Config $config) {}

        public function extend(IMail $mail): IMail
        {
            return new FooterDecorator($mail, $this->config->getExtension('footer')['text']);
        }
    }

    class FooterDecorator extends MailDecorator
    {
        public function __construct(IMail $mail, private string $text)
        {
            parent::__construct($mail);
        }

        public function getHtmlBody(): string
        {
            return parent::getHtmlBody() . "<p>{$this->text}</p>";
        }
    }

The factory must be registered as a service, its configuration is placed in `extensions` section:

    services:
        - App\Mail\Footer

    mailino:
        extensions:
            footer:
                text: 'Sent by Mailino'

<a name="preview"></a>
## Preview

Mail can be rendered without sending, e.g. for preview in administration. Plain text and subject are resolved the same way as when sending.

    $preview = $this->mailino->preview($mail);

    $preview->getSubject();
    $preview->getHtmlBody();
    $preview->getPlainBody();

<a name="events"></a>
## Events

`Exchange` support events. These events are triggers during send process. Supported events are `onSending`, `onSuccess`, `onError`. All the events accept `IMail` object as the first parameter, `onError` gets also thrown exception (any `Throwable`) as the second parameter. The exception is re-thrown after `onError` handlers are called.

The code below show an example of handling exchange events.

    $this->exchange->onSending[] = function (IMail $mail) {
        $this->logger->log('Started sending email with subject %1', $mail->getSubject());
    };

    $this->exchange->onSuccess[] = function (IMail $mail) {
        $this->logger->log('Sending email with subject %1 succeeded', $mail->getSubject());
    };

    $this->exchange->onError[] = function (IMail $mail, \Throwable $exception) {
        $this->logger->log('Sending email with subject %1 failed: %2', $mail->getSubject(), $exception->getMessage());
    };

<a name="ssl-verification"></a>
## SSL verification

Mailino does not change SSL settings. Earlier versions disabled SSL certificate verification globally for the whole PHP process by default. `MailExchange::setVerifySsl()` is deprecated and has no effect now.

If SMTP server uses self-signed certificate (e.g. in development), disable verification for SMTP connection only in Nette mail configuration:

    mail:
        smtp: true
        host: localhost
        context:
            ssl:
                verify_peer: false
                verify_peer_name: false
                allow_self_signed: true
