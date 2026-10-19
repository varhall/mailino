<?php

declare(strict_types=1);

namespace Varhall\Mailino\Extensions\Mjml;

use Varhall\Mailino\Exceptions\MjmlException;

/**
 * Minimal HTTP client for MJML API
 */
class CurlClient
{
    /**
     * @param array<int, mixed> $options  curl options
     * @return array{status: int, body: string}
     */
    public function request(string $url, array $options): array
    {
        if (!\extension_loaded('curl')) {
            throw new MjmlException('MJML API requires "curl" PHP extension.');
        }

        $request = curl_init($url);
        curl_setopt_array($request, $options + [ CURLOPT_RETURNTRANSFER => true ]);

        $body = curl_exec($request);

        if (!is_string($body)) {
            throw new MjmlException(sprintf('MJML API request failed: %s', curl_error($request)));
        }

        return [ 'status' => (int) curl_getinfo($request, CURLINFO_HTTP_CODE), 'body' => $body ];
    }
}
