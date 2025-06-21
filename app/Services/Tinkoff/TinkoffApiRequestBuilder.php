<?php

namespace App\Services\Tinkoff;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

readonly class TinkoffApiRequestBuilder
{
    private Client $client;

    public function __construct(
        private string $apiToken,
        private string $apiUrl
    ) {
        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => $this->getDefaultHeaders(),
            'verify' => false,
        ]);
    }

    public function makeRequest(string $endpoint, array $data = []): array
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => empty($data) ? (object)[] : $data,
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];

        } catch (GuzzleException $e) {
            throw new RuntimeException('Tinkoff Invest API error: ' . $e->getMessage());
        }
    }

    private function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }
}
