<?php

namespace XD\Spotify\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;

class SpotifyService
{
    use Configurable;

    private static string $market = 'NL';

    private Client $client;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $clientId = Environment::getEnv('SPOTIFY_CLIENT_ID');
        $clientSecret = Environment::getEnv('SPOTIFY_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('SPOTIFY_CLIENT_ID and SPOTIFY_CLIENT_SECRET must be set in .env');
        }

        $response = $this->client->post('https://accounts.spotify.com/api/token', [
            'form_params' => ['grant_type' => 'client_credentials'],
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$clientId}:{$clientSecret}"),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    public function getShowEpisodes(string $showId): array
    {
        $token = $this->getAccessToken();
        $market = self::config()->get('market');
        $episodes = [];
        $url = "https://api.spotify.com/v1/shows/{$showId}/episodes";
        $query = ['limit' => 50, 'market' => $market];

        while ($url) {
            $response = $this->client->get($url, [
                'headers' => ['Authorization' => "Bearer {$token}"],
                'query'   => $query,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $episodes = array_merge($episodes, $data['items'] ?? []);

            $url = $data['next'] ?? null;
            $query = [];
        }

        return $episodes;
    }

    public function downloadImage(string $url): ?string
    {
        try {
            return $this->client->get($url)->getBody()->getContents();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getShow(string $showId): array
    {
        $token = $this->getAccessToken();
        $market = self::config()->get('market');

        $response = $this->client->get("https://api.spotify.com/v1/shows/{$showId}", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'query'   => ['market' => $market],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
