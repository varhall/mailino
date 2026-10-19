<?php

namespace Tests\Mailino\Extensions\Mjml;

use Qferrer\Mjml\Exception\ApiException;
use Qferrer\Mjml\Exception\CurlException;
use Qferrer\Mjml\Http\CurlInterface;
use Qferrer\Mjml\Http\CurlResponse;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
use Tests\Engine\HttpServer;
use Varhall\Mailino\Extensions\Mjml\MjmlHttpApi;

require_once __DIR__ . '/../../../bootstrap.php';

class MjmlHttpApiTest extends BaseTestCase
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

    /** @dataProvider getUrls */
    public function testGetHtmlEndpoint($url, $expected)
    {
        $curl = \Mockery::mock(CurlInterface::class);
        $curl->shouldReceive('request')
            ->once()
            ->with($expected, \Mockery::on(function (array $options) {
                return $options[CURLOPT_POSTFIELDS] === json_encode([ 'mjml' => '<mjml></mjml>' ])
                    && in_array('Authorization: Basic ' . base64_encode('id:secret'), $options[CURLOPT_HTTPHEADER]);
            }))
            ->andReturn(new CurlResponse(json_encode([ 'html' => '<html></html>' ]), 200));

        $api = new MjmlHttpApi('id', 'secret', $url, $curl);

        Assert::equal('<html></html>', $api->getHtml('<mjml></mjml>'));
    }

    public function testGetMjmlVersion()
    {
        $curl = \Mockery::mock(CurlInterface::class);
        $curl->shouldReceive('request')->andReturn(new CurlResponse(json_encode([ 'html' => '', 'mjml_version' => '4.15.3' ]), 200));

        $api = new MjmlHttpApi('id', 'secret', 'https://mjml.local/v1', $curl);

        Assert::equal('4.15.3', $api->getMjmlVersion());
    }

    public function testHttpError()
    {
        $curl = \Mockery::mock(CurlInterface::class);
        $curl->shouldReceive('request')->andReturn(new CurlResponse(json_encode([ 'message' => 'Forbidden' ]), 401));

        $api = new MjmlHttpApi('id', 'secret', 'https://mjml.local/v1', $curl);

        Assert::exception(fn() => $api->getHtml('<mjml></mjml>'), ApiException::class, 'Unexpected HTTP code: 401. Api Error Message: "Forbidden".');
    }

    public function testInvalidJson()
    {
        $curl = \Mockery::mock(CurlInterface::class);
        $curl->shouldReceive('request')->andReturn(new CurlResponse('not a json', 200));

        $api = new MjmlHttpApi('id', 'secret', 'https://mjml.local/v1', $curl);

        Assert::exception(fn() => $api->getHtml('<mjml></mjml>'), ApiException::class, '~^Unable to decode the JSON response~');
    }

    public function testTimeouts()
    {
        $curl = \Mockery::mock(CurlInterface::class);
        $curl->shouldReceive('request')
            ->with(\Mockery::any(), \Mockery::on(fn(array $options) => $options[CURLOPT_TIMEOUT] === 5 && $options[CURLOPT_CONNECTTIMEOUT] === 2))
            ->andReturn(new CurlResponse(json_encode([ 'html' => 'x' ]), 200));

        $api = new MjmlHttpApi('id', 'secret', null, $curl, 5, 2);

        Assert::equal('x', $api->getHtml('<mjml></mjml>'));
    }

    public function testInvalidValidationLevel()
    {
        Assert::exception(fn() => new MjmlHttpApi('id', 'secret', null, null, 30, 10, 'foo'), \InvalidArgumentException::class);
    }

    public function testIntegration()
    {
        $server = new HttpServer(FIXTURES_DIR . '/http/router.php');

        try {
            $api = new MjmlHttpApi('app', 'secret', $server->getUrl() . '/v1/');
            Assert::equal('<html><body>Hello</body></html>', $api->getHtml('<mjml><mj-body>Hello</mj-body></mjml>'));
            Assert::equal('4.15.3', $api->getMjmlVersion());

            // soft validation ignores MJML errors
            Assert::noError(fn() => $api->getHtml('<mjml><mj-invalid/></mjml>'));

            $strict = new MjmlHttpApi('app', 'secret', $server->getUrl() . '/v1', null, 30, 10, MjmlHttpApi::VALIDATION_STRICT);
            Assert::exception(fn() => $strict->getHtml('<mjml><mj-invalid/></mjml>'), ApiException::class, '~MJML validation failed: Line 1 \(mj-invalid\)~');

            $unauthorized = new MjmlHttpApi('app', 'wrong', $server->getUrl() . '/v1');
            Assert::exception(fn() => $unauthorized->getHtml('<mjml></mjml>'), ApiException::class, '~401.*Unauthorized~');
        } finally {
            $server->stop();
        }
    }

    public function testIntegration_Timeout()
    {
        // non-routable address - connection must fail within connect timeout instead of hanging forever
        $api = new MjmlHttpApi('app', 'secret', 'http://10.255.255.1/v1', null, 2, 1);

        $start = microtime(true);
        Assert::exception(fn() => $api->getHtml('<mjml></mjml>'), CurlException::class);
        Assert::true(microtime(true) - $start < 5);
    }

    protected function tearDown()
    {
        \Mockery::close();
    }
}

(new MjmlHttpApiTest())->run();
