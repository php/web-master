<?php

namespace App\GitHub;

use RuntimeException;

class OAuthClient
{
    protected $clientId;

    protected $clientSecret;

    public const GITHUB_USER_AGENT = 'php.net repository management (main.php.net, systems@php.net, johannes@php.net)';

    public function __construct($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getRequestCodeUrl()
    {
        return 'https://github.com/login/oauth/authorize?client_id=' . urlencode($this->clientId);
    }

    public function requestAccessToken($code)
    {
        $headers = [];
        $headers[] = 'Content-type: application/x-www-form-urlencoded';
        $headers[] = 'Accept: application/json';

        $data = http_build_query(
            [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code
            ]
        );

        $options['method'] = 'POST';
        $options['user_agent'] = self::GITHUB_USER_AGENT;
        $options['header'] = implode(PHP_EOL, $headers);
        $options['content'] = $data;

        $context = stream_context_create(['http' => $options]);
        $response = file_get_contents('https://github.com/login/oauth/access_token', false, $context);

        if (!$response) {
            throw new RuntimeException('Failed GitHub request access token');
        }

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['error'])) {
            throw new RuntimeException($result['error_description']);
        }

        return $result;
    }
}