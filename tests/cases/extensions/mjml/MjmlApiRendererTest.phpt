<?php

namespace Tests\Mailino\Extensions\Mjml;

use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Engine\HttpServer;
use Varhall\Mailino\Exceptions\MjmlException;
use Varhall\Mailino\Extensions\Mjml\CurlClient;
use Varhall\Mailino\Extensions\Mjml\MjmlApiRenderer;

require_once __DIR__ . '/../../../bootstrap.php';

class MjmlApiRendererTest extends BaseTestCase
{
    public function getUrls()
    {
        return [
            [ null,                         'https://api.mjml.io/v1/render' ],
            [ 'https://mjml.local/v1',      'https://mjml.local/v1/render' ],
            [ 'https://mjml.local/v1/',     'https://mjml.local/v1/render' ],
            [ 'http://localhost:8080',      'http://localhost:8080/render' ],
        ];
    }

    private function client(int $status, string $body, ?callable $assertOptions = null): CurlClient
    {
        $client = \Mockery::mock(CurlClient::class);
        $client->shouldReceive('request')
            ->andReturnUsing(function (string $url, array $options) use ($status, $body, $assertOptions) {
                if ($assertOptions) {
                    $assertOptions($url, $options);
                }

                return [ 'status' => $status, 'body' => $body ];
            });

        return $client;
    }

    /** @dataProvider getUrls */
    public function testRender_Endpoint($url, $expected)
    {
        $client = $this->client(200, json_encode([ 'html' => '<html></html>' ]), function (string $requestUrl, array $options) use ($expected) {
            Assert::equal($expected, $requestUrl);
            Assert::equal(json_encode([ 'mjml' => '<mjml></mjml>' ]), $options[CURLOPT_POSTFIELDS]);
            Assert::contains('Authorization: Basic ' . base64_encode('id:secret'), $options[CURLOPT_HTTPHEADER]);
        });

        $renderer = new MjmlApiRenderer('id', 'secret', $url, client: $client);

        Assert::equal('<html></html>', $renderer->render('<mjml></mjml>'));
    }

    public function testTimeouts()
    {
        $client = $this->client(200, json_encode([ 'html' => 'x' ]), function (string $url, array $options) {
            Assert::equal(5, $options[CURLOPT_TIMEOUT]);
            Assert::equal(2, $options[CURLOPT_CONNECTTIMEOUT]);
        });

        Assert::equal('x', (new MjmlApiRenderer('id', 'secret', null, 5, 2, client: $client))->render('<mjml></mjml>'));
    }

    public function testMjmlVersion()
    {
        $renderer = new MjmlApiRenderer('id', 'secret', client: $this->client(200, json_encode([ 'html' => '', 'mjml_version' => '4.15.3' ])));

        Assert::equal('4.15.3', $renderer->getMjmlVersion());
    }

    public function testHttpError()
    {
        $renderer = new MjmlApiRenderer('id', 'secret', client: $this->client(401, json_encode([ 'message' => 'Forbidden' ])));

        Assert::exception(fn() => $renderer->render('<mjml></mjml>'), MjmlException::class, 'Unexpected HTTP code: 401. Api Error Message: "Forbidden".');
    }

    public function testInvalidJson()
    {
        $renderer = new MjmlApiRenderer('id', 'secret', client: $this->client(200, 'not a json'));

        Assert::exception(fn() => $renderer->render('<mjml></mjml>'), MjmlException::class, '~^Unable to decode MJML API response~');
    }

    public function testInvalidValidationLevel()
    {
        Assert::exception(fn() => new MjmlApiRenderer('id', 'secret', validationLevel: 'foo'), \InvalidArgumentException::class);
    }

    public function testIntegration()
    {
        $server = new HttpServer(FIXTURES_DIR . '/http/router.php');

        try {
            $renderer = new MjmlApiRenderer('app', 'secret', $server->getUrl() . '/v1/');
            Assert::equal('<html><body>Hello</body></html>', $renderer->render('<mjml><mj-body>Hello</mj-body></mjml>'));
            Assert::equal('4.15.3', $renderer->getMjmlVersion());

            // soft validation ignores MJML errors
            Assert::noError(fn() => $renderer->render('<mjml><mj-invalid/></mjml>'));

            $strict = new MjmlApiRenderer('app', 'secret', $server->getUrl() . '/v1', validationLevel: MjmlApiRenderer::VALIDATION_STRICT);
            Assert::exception(fn() => $strict->render('<mjml><mj-invalid/></mjml>'), MjmlException::class, '~MJML validation failed: Line 1 \(mj-invalid\)~');

            $unauthorized = new MjmlApiRenderer('app', 'wrong', $server->getUrl() . '/v1');
            Assert::exception(fn() => $unauthorized->render('<mjml></mjml>'), MjmlException::class, '~401.*Unauthorized~');
        } finally {
            $server->stop();
        }
    }

    public function testIntegration_Timeout()
    {
        // non-routable address - connection must fail within connect timeout instead of hanging forever
        $renderer = new MjmlApiRenderer('app', 'secret', 'http://10.255.255.1/v1', 2, 1);

        $start = microtime(true);
        Assert::exception(fn() => $renderer->render('<mjml></mjml>'), MjmlException::class);
        Assert::true(microtime(true) - $start < 5);
    }

    protected function tearDown()
    {
        \Mockery::close();
    }
}

(new MjmlApiRendererTest())->run();
