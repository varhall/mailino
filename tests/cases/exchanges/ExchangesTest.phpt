<?php

namespace Tests\Mailino\Exchanges;

use Latte\Engine;
use Nette\Utils\FileSystem;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Entities\IMail;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Exchanges\IExchange;
use Varhall\Mailino\Exchanges\LogExchange;
use Varhall\Mailino\Exchanges\MailExchange;
use Varhall\Mailino\Exchanges\NullExchange;

require_once __DIR__ . '/../../bootstrap.php';

class ExchangesTest extends ContainerTestCase
{
    private function createMail(): IMail
    {
        return (new Mail(new Engine(), $this->getContainer(), FIXTURES_DIR . '/templates'))
            ->setTemplate('subject/test')
            ->setData([ 'name' => 'Pepa' ])
            ->setFrom('from@test.cz')
            ->addTo('to@test.cz');
    }

    public function testNullExchange()
    {
        $exchange = new NullExchange();

        $events = [];
        $exchange->onSending[] = function () use (&$events) {
            $events[] = 'sending';
        };
        $exchange->onSuccess[] = function () use (&$events) {
            $events[] = 'success';
        };

        $exchange->send($this->createMail());

        Assert::equal([ 'sending', 'success' ], $events);
        Assert::count(1, $exchange->getMessages());
        Assert::equal('Hello Pepa & co', $exchange->getMessages()[0]->getSubject());

        $exchange->clear();
        Assert::count(0, $exchange->getMessages());
    }

    public function testNullExchange_Error()
    {
        $exchange = new NullExchange();

        $errors = 0;
        $exchange->onError[] = function () use (&$errors) {
            $errors++;
        };

        Assert::exception(fn() => $exchange->send($this->createMail()->clearTo()), \Nette\InvalidStateException::class);
        Assert::equal(1, $errors);
    }

    public function testLogExchange()
    {
        $dir = TMP_DIR . '/mails';
        FileSystem::delete($dir);

        $exchange = new LogExchange($dir);
        $exchange->send($this->createMail());

        $files = glob("{$dir}/*.eml");
        Assert::count(1, $files);
        Assert::contains('Subject: Hello Pepa & co', file_get_contents($files[0]));
        Assert::contains('To: to@test.cz', file_get_contents($files[0]));
    }

    public function testInterface()
    {
        Assert::type(IExchange::class, new NullExchange());
        Assert::type(IExchange::class, $this->getContainer()->getByType(MailExchange::class));
        Assert::type(MailExchange::class, $this->getContainer()->getByType(IExchange::class));
    }

    public function testReplaceExchange()
    {
        $container = $this->createContainer([ 'services' => [ 'mailino.exchange' => NullExchange::class ] ]);

        Assert::type(NullExchange::class, $container->getByType(IExchange::class));
    }
}

(new ExchangesTest())->run();
