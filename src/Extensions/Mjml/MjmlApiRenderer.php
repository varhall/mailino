<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions\Mjml;

use Varhall\Mailino\Exceptions\MjmlException;

/**
 * Renders MJML using MJML HTTP API. Works with official mjml.io API and any self-hosted service
 * implementing the same contract (POST {url}/render).
 *
 * @see https://mjml.io/api/documentation/
 */
class MjmlApiRenderer implements IMjmlRenderer
{
    public const DEFAULT_URL = 'https://api.mjml.io/v1';

    public const VALIDATION_SOFT = 'soft';
    public const VALIDATION_STRICT = 'strict';

    private string $url;

    private string $credentials;

    private CurlClient $client;

    /**
     * @param int $timeout          total request timeout in seconds
     * @param int $connectTimeout   connection timeout in seconds
     * @param string $validationLevel  strict = throw exception when API returns MJML validation errors
     */
    public function __construct(
        string $appId,
        string $secretKey,
        ?string $url = null,
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 10,
        private readonly string $validationLevel = self::VALIDATION_SOFT,
        ?CurlClient $client = null
    ) {
        if (!in_array($validationLevel, [ self::VALIDATION_SOFT, self::VALIDATION_STRICT ], true)) {
            throw new \InvalidArgumentException("Invalid MJML validation level '{$validationLevel}'");
        }

        $this->url = rtrim($url ?? self::DEFAULT_URL, '/');
        $this->credentials = base64_encode("{$appId}:{$secretKey}");
        $this->client = $client ?? new CurlClient();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function render(string $mjml): string
    {
        $html = $this->request($mjml)['html'] ?? '';

        return is_string($html) ? $html : '';
    }

    public function getMjmlVersion(): string
    {
        $version = $this->request('<mjml></mjml>')['mjml_version'] ?? 'unknown';

        return is_string($version) ? $version : 'unknown';
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $mjml): array
    {
        $response = $this->client->request($this->url . '/render', [
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_MAXREDIRS       => 10,
            CURLOPT_TIMEOUT         => $this->timeout,
            CURLOPT_CONNECTTIMEOUT  => $this->connectTimeout,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => json_encode([ 'mjml' => $mjml ], JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER      => [
                'Content-Type: application/json',
                "Authorization: Basic {$this->credentials}",
            ],
        ]);

        $data = json_decode($response['body'], true);

        if (!is_array($data)) {
            throw new MjmlException(sprintf('Unable to decode MJML API response: "%s".', json_last_error_msg()));
        }

        if ($response['status'] !== 200) {
            $message = is_string($data['message'] ?? null) ? $data['message'] : 'Unknown Error';
            throw new MjmlException(sprintf('Unexpected HTTP code: %d. Api Error Message: "%s".', $response['status'], $message));
        }

        if ($this->validationLevel === self::VALIDATION_STRICT && !empty($data['errors']) && is_array($data['errors'])) {
            $messages = array_map(
                fn($error) => is_array($error)
                    ? (string) ($error['formattedMessage'] ?? $error['message'] ?? json_encode($error))
                    : (is_scalar($error) ? (string) $error : ''),
                $data['errors']
            );

            throw new MjmlException('MJML validation failed: ' . implode('; ', $messages));
        }

        return $data;
    }
}
