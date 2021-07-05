<?php

namespace App\GitHub;

class Client
{
    protected $token;

    public const GITHUB_USER_AGENT = 'php.net repository management (main.php.net, systems@php.net, johannes@php.net)';

    public function __construct($token = null)
    {
        $this->token = $token;
    }

    public function me()
    {
        return $this->query('/user');
    }

    protected function query($endpoint, $method = 'GET', $headers = [])
    {
        $options = [
            'method' => $method,
            'user_agent' => self::GITHUB_USER_AGENT
        ];
        
        if ($token = $this->token) {
            $headers[] = 'Authorization: token ' . urlencode($token);
        }

        $options['header'] = implode(PHP_EOL, $headers);

        $context = stream_context_create(['http' => $options]);
        $url = 'https://api.github.com' . $endpoint;
        $response = file_get_contents($url, false, $context);

        if (!$response) {
            throw new \RuntimeException('Failed GitHub request: ' . $endpoint);
        }

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['error'])) {
            throw new \RuntimeException($result['error_description']);
        }

        return $result;
    }
}