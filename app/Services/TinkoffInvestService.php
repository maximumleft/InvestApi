<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class TinkoffInvestService
{
    protected Client $client;
    protected string $apiToken;
    protected string $apiUrl;
    protected bool $sandbox;

    public function __construct()
    {
        $this->apiToken = config('tinkoff-invest.api_token');
        $this->apiUrl = config('tinkoff-invest.api_url');

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ],
            'verify' => false, // Отключает проверку SSL
        ]);
    }

    /**
     * Получение списка счетов
     */
    public function getAccounts(): array
    {
        try {
            $response = $this->client->post('tinkoff.public.invest.api.contract.v1.UsersService/GetAccounts', [
                'json' => (object)[], // Важно: пустой объект, а не массив
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['accounts'])) {
                throw new \RuntimeException('Invalid API response format');
            }

            return $data['accounts'];

        } catch (GuzzleException $e) {
            throw new \RuntimeException('Tinkoff Invest API error: ' . $e->getMessage());
        }
    }

    /**
     * Получение портфеля по accountId
     */
    public function getPortfolio(string $accountId): array
    {
        try {
            $response = $this->client->get("portfolio?brokerAccountId={$accountId}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Tinkoff Invest API error: ' . $e->getMessage());
        }
    }

    /**
     * Получение списка позиций в портфеле
     */
    public function getPositions(string $accountId): array
    {
        try {
            $response = $this->client->get("portfolio/positions?brokerAccountId={$accountId}");
            $data = json_decode($response->getBody()->getContents(), true);

            return $data['payload']['positions'] ?? [];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Tinkoff Invest API error: ' . $e->getMessage());
        }
    }

    /**
     * Получение информации об инструменте по FIGI
     */
    public function getInstrumentByFigi(string $figi): array
    {
        try {
            $response = $this->client->get("market/search/by-figi?figi={$figi}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Tinkoff Invest API error: ' . $e->getMessage());
        }
    }
}
