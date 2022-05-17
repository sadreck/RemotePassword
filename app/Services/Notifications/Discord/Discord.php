<?php
namespace App\Services\Notifications\Discord;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Discord
{
    /**
     * @param Client $httpClient
     */
    public function __construct(protected Client $httpClient)
    {
        //
    }

    /**
     * @param string $url
     * @param array $data
     * @return ResponseInterface|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(string $url, array $data) : ?ResponseInterface
    {
        if (empty($url)) {
            throw new \Exception('Discord webhook URL is missing');
        }

        $response = $this->httpClient->post($url, ['json' => $data]);

        return $response;
    }
}
