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
            $response = $this->client->post(
                'tinkoff.public.invest.api.contract.v1.OperationsService/GetPortfolio',
                [
                    'json' => [
                        'accountId' => $accountId,
                        'currency' => 'RUB' // Базовая валюта для оценки портфеля
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            $portfolioData = json_decode($response->getBody()->getContents(), true);

            // Стандартизируем структуру ответа
            return [
                'total_amount' => $portfolioData['totalAmountPortfolio']['value'] ?? 0,
                'currency' => $portfolioData['totalAmountPortfolio']['currency'] ?? 'RUB',
                'positions' => array_map(function($position) {
                    return [
                        'figi' => $position['figi'],
                        'instrument_type' => $position['instrumentType'],
                        'quantity' => $position['quantity']['units'] ?? 0,
                        'average_price' => $position['averagePositionPrice']['value'] ?? 0,
                        'expected_yield' => $position['expectedYield']['value'] ?? 0,
                        'current_price' => $position['currentPrice']['value'] ?? 0,
                        'currency' => $position['averagePositionPrice']['currency'] ?? 'RUB'
                    ];
                }, $portfolioData['positions'] ?? []),
                'status' => 'success'
            ];

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
            $response = $this->client->post(
                'tinkoff.public.invest.api.contract.v1.OperationsService/GetPositions',
                [
                    'json' => [
                        'accountId' => $accountId
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            $responseData = json_decode($response->getBody()->getContents(), true);

            // Стандартизируем структуру ответа
            $positions = [];

            // Обрабатываем ценные бумаги
            foreach ($responseData['securities'] ?? [] as $security) {
                $positions[] = [
                    'figi' => $security['figi'],
                    'ticker' => $security['ticker'] ?? null,
                    'name' => $security['name'] ?? null,
                    'instrument_type' => $security['instrumentType'],
                    'balance' => $security['balance'],
                    'blocked' => $security['blocked'] ?? 0,
                    'position_uid' => $security['positionUid'] ?? null,
                    'average_price' => $security['averagePositionPrice']['value'] ?? null,
                    'expected_yield' => $security['expectedYield']['value'] ?? null,
                    'current_price' => $security['currentPrice']['value'] ?? null,
                    'currency' => $security['averagePositionPrice']['currency'] ?? 'RUB',
                    'type' => 'security'
                ];
            }

            // Обрабатываем валютные позиции
            foreach ($responseData['currencies'] ?? [] as $currency) {
                $positions[] = [
                    'currency' => $currency['currency'],
                    'balance' => $currency['balance'],
                    'blocked' => $currency['blocked'] ?? 0,
                    'type' => 'currency'
                ];
            }

            // Обрабатываем фьючерсы (если есть)
            foreach ($responseData['futures'] ?? [] as $future) {
                $positions[] = [
                    'figi' => $future['figi'],
                    'ticker' => $future['ticker'] ?? null,
                    'name' => $future['name'] ?? null,
                    'instrument_type' => 'Future',
                    'balance' => $future['balance'],
                    'blocked' => $future['blocked'] ?? 0,
                    'position_uid' => $future['positionUid'] ?? null,
                    'average_price' => $future['averagePositionPrice']['value'] ?? null,
                    'expected_yield' => $future['expectedYield']['value'] ?? null,
                    'current_price' => $future['currentPrice']['value'] ?? null,
                    'currency' => $future['averagePositionPrice']['currency'] ?? 'RUB',
                    'type' => 'future'
                ];
            }

            return $positions;

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
            $response = $this->client->post(
                'tinkoff.public.invest.api.contract.v1.InstrumentsService/GetInstrumentBy',
                [
                    'json' => [
                        'idType' => 1, // 1 = FIGI
                        'id' => $figi,
                        'classCode' => '' // Можно указать класс инструмента или оставить пустым
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ]
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            // Стандартизируем структуру ответа
            return [
                'figi' => $data['instrument']['figi'] ?? null,
                'ticker' => $data['instrument']['ticker'] ?? null,
                'isin' => $data['instrument']['isin'] ?? null,
                'name' => $data['instrument']['name'] ?? null,
                'type' => $data['instrument']['instrumentType'] ?? null,
                'currency' => $data['instrument']['currency'] ?? null,
                'lot' => $data['instrument']['lot'] ?? 1,
                'min_price_increment' => $data['instrument']['minPriceIncrement'] ?? null,
                'exchange' => $data['instrument']['exchange'] ?? null,
                'country' => $data['instrument']['countryOfRisk'] ?? null,
                'sector' => $data['instrument']['sector'] ?? null,
                'class_code' => $data['instrument']['classCode'] ?? null
            ];

        } catch (GuzzleException $e) {
            throw new \RuntimeException('Tinkoff Invest API error: ' . $e->getMessage());
        }
    }
}
