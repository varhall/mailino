<?php

namespace Tests\Mailino\Exchanges;

use Nette\InvalidStateException;
use Nette\Utils\FileSystem;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Tests\Engine\Helpers;
use Varhall\Mailino\Entities\Mail;
use Varhall\Mailino\Exchanges\IExchange;
use Varhall\Mailino\Exchanges\LogExchange;
use Varhall\Mailino\Exchanges\MailExchange;
use Varhall\Mailino\Exchanges\NullExchange;

require_once __DIR__ . '/../../bootstrap.php';

class ExchangesTest extends ContainerTestCase
{
    private function createMail(): Mail
    {
        return (new Mail('subject/test', [ 'name' => 'Pepa' ]))
            ->setFrom('from@test.cz')
            ->addTo('to@test.cz');
    }

    public function testNullExchange()
    {
        $exchange = new NullExchange(Helpers::renderer());

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
        $exchange = new NullExchange(Helpers::renderer());

        $errors = 0;
        $exchange->onError[] = function () use (&$errors) {
            $errors++;
        };

        Assert::exception(fn() => $exchange->send($this->createMail()->clearTo()), InvalidStateException::class);
        Assert::equal(1, $errors);
    }

    public function testLogExchange()
    {
        $dir = TMP_DIR . '/mails';
        FileSystem::delete($dir);

        $exchange = new LogExchange($dir, Helpers::renderer());
        $exchange->send($this->createMail());

        $files = glob("{$dir}/*.eml");
        Assert::count(1, $files);
        Assert::contains('Subject: Hello Pepa & co', file_get_contents($files[0]));
        Assert::contains('To: to@test.cz', file_get_contents($files[0]));
    }

    public function testContainer()
    {
        Assert::type(MailExchange::class, $this->getContainer()->getByType(IExchange::class));
    }

    public function testContainer_ReplaceExchange()
    {
        $container = $this->createContainer([ 'services' => [ 'mailino.exchange' => NullExchange::class ] ]);

        Assert::type(NullExchange::class, $container->getByType(IExchange::class));
    }
}

(new ExchangesTest())->run();
