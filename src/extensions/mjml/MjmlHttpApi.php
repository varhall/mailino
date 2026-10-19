<?php

namespace Varhall\Mailino\Extensions\Mjml;

use Qferrer\Mjml\ApiInterface;
use Qferrer\Mjml\Exception\ApiException;
use Qferrer\Mjml\Http\Curl;
use Qferrer\Mjml\Http\CurlInterface;

/**
 * MJML HTTP API client with configurable endpoint. Works with official mjml.io API
 * and any self-hosted service implementing the same contract (POST {url}/render).
 *
 * @see https://mjml.io/api/documentation/
 */
class MjmlHttpApi implements ApiInterface
{
    public const DEFAULT_URL = 'https://api.mjml.io/v1';

    private string $url;
    private string $credentials;
    private CurlInterface $curl;

    public function __construct(string $appId, string $secretKey, ?string $url = null, ?CurlInterface $curl = null)
    {
        if (!\extension_loaded('curl'))
            throw new \LogicException(sprintf('You cannot use the "%s" as the "curl" extension is not installed.', self::class));

        $this->url = rtrim($url ?? self::DEFAULT_URL, '/');
        $this->credentials = base64_encode("{$appId}:{$secretKey}");
        $this->curl = $curl ?? new Curl();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHtml(string $mjml): string
    {
        return $this->getResult($mjml)['html'] ?? '';
    }

    public function getMjmlVersion(): string
    {
        return $this->getResult('<mjml></mjml>')['mjml_version'] ?? 'unknown';
    }

    private function getResult(string $mjml): array
    {
        $response = $this->curl->request($this->url . '/render', [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_ENCODING        => 'UTF-8',
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => 0,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => json_encode([ 'mjml' => $mjml ]),
            CURLOPT_HTTPHEADER      => [
                'Content-Type: application/json',
                "Authorization: Basic {$this->credentials}",
            ],
        ]);

        $data = @json_decode($response->getContent(), true);

        if (!is_array($data))
            throw new ApiException(sprintf('Unable to decode the JSON response: "%s".', json_last_error_msg()));

        $httpCode = $response->getStatusCode();

        if ($httpCode !== 200)
            throw new ApiException(sprintf('Unexpected HTTP code: %s. Api Error Message: "%s".', $httpCode, $data['message'] ?? 'Unknown Error'));

        return $data;
    }
}
