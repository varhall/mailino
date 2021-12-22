# Mailino

- [Installation](#model-definition)
- [Setup](#setup)
- [Basic usage](#basic-usage)
- [Extension](#extensions)
  - [Prefix](#prefix)
  - [MJML](#mjml)
- [Events](#events)

Mailino is Nette mailing extension to create modular structure of mail message and mailing exchanges.

> {tip} Before getting started, be sure to have working Nette project and properly configured a mailing in your application's `config/local.neon` configuration file. For more information on configuring mailing, check out [the mailing documentation](https://doc.nette.org/en/3.1/mailing).

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
        sender:
            email: 'sender@test.com'
            name: 'Sender'

        extensions:
            prefix:
                subject: 'TST'
    
            mjml.api:
                id: 'abcdef-1234-5678-ghijkl'
                secret: 'ghijkl-5678-1234-abcdef'
    
            mjml.binary:
                binary: '%appDir%/../node_modules/.bin/mjml'

<a name="basic-usage"></a>
## Basic Usage
Create an email object using Mailino factory first. This object is automatically configured from extension config. Sender is already defined. Then specify template file relative from `template_dir`. Template files are without extensions. Mailino will look for:

- %appDir%/templates/emails/accounts/register.html.latte
- %appDir%/templates/emails/accounts/register.plain.latte

This very simple example shows, how to define mail service and send email using Mailino.

    <?php

    namespace App\Services;

    use Varhall\Mailino\Mailino;
    use Varhall\Mailino\Exchanges\MailExchange;

    class EmailsService
    {
        /** @var Mailino */
        private $mailino;

        /** @var MailExchange */
        private $exchange;

        public function __construct(Mailino $mailino, MailExchange $exchange)
        {
            $this->mailino = $mailino;
            $this->exchange = $exchange;
        }

        public function send()
        {
            $mail = $this->mailino->create('accounts/register', [ 'email' => 'karl.hermann@gmail.com', 'password' => '123456Ab+' ])
                        ->setSubject('Welcome aboard')
                        ->addTo('karl.hermann@gmail.com');

            $this->exchange->send($mail);
        }
    }

<a name="extensions"></a>
## Extensions

Mailino deliveres some extensions by default. These are used to extend `IMail` class. Mail is extended simply by calling `extend` method. These extensions can be arbitrarily combined, like shows the example below.

    <?php

    use Varhall\Mailino\Extensions\Prefix;
    use Varhall\Mailino\Extensions\MjmlApi;

    $mail = $this->mailino->create('template', $data)
                        ->extend(Prefix::class)
                        ->extend(MjmlApi:class)
                        ->setSubject('Welcome aboard');        

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

Current email will have subject `[TST] Welcome aboard`.

<a name="mjml"></a>
### MJML

[MJML](https://mjml.io) is a markup language designed to reduce the pain of coding a responsive email. Its semantic syntax makes it easy and straightforward and its rich standard components library speeds up your development time and lightens your email codebase. MJML’s open-source engine generates high quality responsive HTML compliant with best practices.

Mailino provides extension to simply use MJML in email templates. To compile MJML template you can use external API service or local binary service.

There are two versions of MJML extension - API and Binary. Both provide the same functions, only their implementation differ. 

Email requires two standard template files for HTML and plaintext. In examples below are required files `template.html.latte` and `template.plain.latte`. MJML can be written in `template.html.latte` and all `Latte` features can be used of course.

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

#### Binary

MJML API extension requires API credentials to be configured in `mjml.api` section in Mailino configuration.

    mailino:
        extensions:
            mjml.binary:
                binary: '%appDir%/../node_modules/.bin/mjml'

Extension is then used by:

    <?php
    
    use Varhall\Mailino\Extensions\BinaryApi;

    $mail = $this->mailino->create('template', $data)
                        ->extend(BinaryApi::class)
                        ->setSubject('Welcome aboard');

Local MJML binary requires Node. Installation can be simply done using

    npm install mjml

<a name="events"></a>
## Events

`Exchange` support events. These events are triggers during send process. Supported events are `onSending`, `onSuccess`, `onError`. All the events accepts `IMail` object as parameter.

The code below show an example of handling exchange events.

    $this->exchange->onSending[] = function ($mail) {
        $this->logger->log('Started sending email with subject %1', $mail->getSubject());
    };

    $this->exchange->onSuccess[] = function ($mail) {
        $this->logger->log('Sending email with subject %1 succeeded', $mail->getSubject());
    };

    $this->exchange->onError[] = function ($mail) {
        $this->logger->log('Sending email with subject %1 failed', $mail->getSubject());
    };
