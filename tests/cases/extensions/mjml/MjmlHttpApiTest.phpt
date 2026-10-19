<?php

namespace Tests\Mailino\Extensions\Mjml;

use Qferrer\Mjml\Exception\ApiException;
use Qferrer\Mjml\Http\CurlInterface;
use Qferrer\Mjml\Http\CurlResponse;
use Tester\Assert;
use Tests\Engine\BaseTestCase;
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

    protected function tearDown()
    {
        \Mockery::close();
    }
}

(new MjmlHttpApiTest())->run();
