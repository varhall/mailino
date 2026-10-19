<?php

namespace Varhall\Mailino\Extensions\Mjml;

use Qferrer\Mjml\Exception\CurlException;
use Qferrer\Mjml\Http\CurlInterface;
use Qferrer\Mjml\Http\CurlResponse;
use Qferrer\Mjml\Http\CurlResponseInterface;

/**
 * Curl client without deprecated curl_close() call (deprecated since PHP 8.5, handle is freed automatically)
 */
class Curl implements CurlInterface
{
    public function request(string $url, array $options = []): CurlResponseInterface
    {
        $request = curl_init($url);

        if ($request === false) {
            throw new CurlException('Unable to initialize Curl.');
        }

        curl_setopt_array($request, $options);

        $data = curl_exec($request);

        if ($data === false) {
            throw new CurlException(sprintf('Curl Error: "%s"', curl_error($request)));
        }

        return new CurlResponse($data, curl_getinfo($request, CURLINFO_HTTP_CODE));
    }
}
