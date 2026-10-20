<?php

namespace Tests\Mailino;

use Nette\Utils\AssertionException;
use Tester\Assert;
use Tests\Engine\ContainerTestCase;
use Varhall\Mailino\Config;
use Varhall\Mailino\Extensions\MjmlApi;
use Varhall\Mailino\Extensions\MjmlBinary;
use Varhall\Mailino\Mailino;

require_once __DIR__ . '/../bootstrap.php';

/**
 * Configuration values known at runtime only (e.g. %env.*% parameters)
 */
class DynamicParametersTest extends ContainerTestCase
{
    private function configuration(): array
    {
        return [ 'mailino' => [
            'template_dir'  => '%env.TEMPLATE_DIR%',
            'from'          => [ 'email' => '%env.FROM_EMAIL%', 'name' => '%env.FROM_NAME%' ],
            'reply_to'      => [ 'email' => '%env.REPLY_EMAIL%' ],
            'return_path'   => '%env.RETURN_PATH%',
            'priority'      => '%env.PRIORITY%',
            'headers'       => [ 'X-Env' => '%env.HEADER%' ],
            'embed_dir'     => '%env.EMBED_DIR%',
            'extensions'    => [
                'prefix'        => [ 'subject' => '%env.PREFIX%' ],
                'mjml.api'      => [
                    'id'        => '%env.MJML_ID%',
                    'secret'    => '%env.MJML_SECRET%',
                    'url'       => '%env.MJML_URL%',
                    'timeout'   => '%env.MJML_TIMEOUT%',
                ],
                'mjml.binary'   => [ 'binary' => [ 'node', '%env.MJML_BINARY%' ] ],
            ],
        ] ];
    }

    private function environment(array $override = []): array
    {
        return [ 'env' => $override + [
            'TEMPLATE_DIR'  => FIXTURES_DIR . '/templates',
            'FROM_EMAIL'    => 'env@test.cz',
            'FROM_NAME'     => 'Env Sender',
            'REPLY_EMAIL'   => 'reply@test.cz',
            'RETURN_PATH'   => 'bounce@test.cz',
            'PRIORITY'      => 2,
            'HEADER'        => 'from-env',
            'EMBED_DIR'     => '/tmp/images',
            'PREFIX'        => 'ENV',
            'MJML_ID'       => 'env-id',
            'MJML_SECRET'   => 'env-secret',
            'MJML_URL'      => 'https://mjml.env/v1',
            'MJML_TIMEOUT'  => 5,
            'MJML_BINARY'   => '/opt/mjml',
        ] ];
    }

    public function testDynamicValues()
    {
        $container = $this->createContainer($this->configuration(), $this->environment());

        $config = $container->getByType(Config::class);
        Assert::equal([ 'id' => 'env-id', 'secret' => 'env-secret', 'url' => 'https://mjml.env/v1', 'timeout' => 5, 'connect_timeout' => 10, 'validation_level' => 'soft' ], $config->getExtension('mjml.api'));
        Assert::equal([ 'binary' => [ 'node', '/opt/mjml' ], 'timeout' => 30 ], $config->getExtension('mjml.binary'));

        Assert::equal('https://mjml.env/v1', $container->getByType(MjmlApi::class)->getRenderer()->getUrl());

        $mailino = $container->getByType(Mailino::class);
        $mail = $mailino->create('subject/test', [ 'name' => 'Pepa' ])->extend(\Varhall\Mailino\Extensions\Prefix::class);

        Assert::equal('env@test.cz', $mail->getFrom()->getEmail());
        Assert::equal('Env Sender', $mail->getFrom()->getName());
        Assert::equal('reply@test.cz', $mail->getReplyTo()[0]->getEmail());
        Assert::equal('bounce@test.cz', $mail->getReturnPath());
        Assert::equal(2, $mail->getPriority());
        Assert::equal('from-env', $mail->getHeader('X-Env'));
        Assert::equal('/tmp/images', $mail->getBasePath());
        Assert::equal('[ENV] Hello Pepa & co', $mailino->preview($mail)->getSubject());
    }

    public function getInvalidEnvironments()
    {
        return [
            'id is not string'      => [ [ 'MJML_ID' => [ 'array' ] ] ],
            'timeout is not int'    => [ [ 'MJML_TIMEOUT' => 'five' ] ],
            'priority is not int'   => [ [ 'PRIORITY' => 'high' ] ],
            'priority out of range' => [ [ 'PRIORITY' => 9 ] ],
            'email is not string'   => [ [ 'FROM_EMAIL' => 123 ] ],
        ];
    }

    /** @dataProvider getInvalidEnvironments */
    public function testInvalidDynamicValue(array $environment)
    {
        // wrong type is reported when container starts, not later during sending
        Assert::exception(
            fn() => $this->createContainer($this->configuration(), $this->environment($environment)),
            AssertionException::class,
            "The dynamic parameter used in 'mailino%a%"
        );
    }
}

(new DynamicParametersTest())->run();
