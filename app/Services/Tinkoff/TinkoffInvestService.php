<?php

namespace App\Services\Tinkoff;

use App\Models\BrokerageAccount;
use App\Models\Positions;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class TinkoffInvestService
{
    private TinkoffApiRequestBuilder $requestBuilder;
    private User $user;

    public function __construct()
    {
        $this->user = User::find(auth()->user()->getAuthIdentifier());

        $this->requestBuilder = new TinkoffApiRequestBuilder(
            $this->user->tinkoff_token_api,
            config('tinkoff-invest.api_url')
        );
    }

    public function getAccounts(): array
    {
        $data = $this->requestBuilder->makeRequest(
            'tinkoff.public.invest.api.contract.v1.UsersService/GetAccounts'
        );

        if (!isset($data['accounts'])) {
            throw new \RuntimeException('Invalid API response format');
        }
        foreach ($data['accounts'] as $account) {
            BrokerageAccount::firstOrCreate(
                ['account_id' => $account['id']],
                [
                    'account_id' => $account['id'],
                    'user_id' => $this->user->id,
                ]
            );
        }

        return $data['accounts'];
    }

    public function getPortfolio(string $accountId): array
    {
        return Cache::remember("instrument_{$accountId}", now()->addHours(3), function () use ($accountId) {

            $data = $this->requestBuilder->makeRequest(
                'tinkoff.public.invest.api.contract.v1.OperationsService/GetPortfolio',
                ['accountId' => $accountId, 'currency' => 'RUB']
            );

            $totalAmount = $data['totalAmountPortfolio'] ?? [];
            $positions = $data['positions'] ?? [];

            return [
                'total_amount' => $totalAmount['units'] ?? 0,
                'currency' => $totalAmount['currency'] ?? 'RUB',
                'positions' => array_map(fn($p) => $this->mapPosition($p), $positions),
                'status' => 'success'
            ];
        });
    }

    private function mapPosition(array $position): array
    {
        $position = Positions::firstOrCreate(
            ['figi' => $position['figi']],
            [
                'figi' => $position['figi'],
                'ticker' => $position['ticker'],
                'quantity' => $position['quantity']['units'] ?? 0,
                'average_price' => (int)($position['averagePositionPrice']['units'] ?? 0) +
                    ($position['averagePositionPrice']['nano'] ?? 0) / 1000000000,
                'expected_yield' => (int)($position['expectedYield']['units'] ?? 0) +
                    ($position['expectedYield']['nano'] ?? 0) / 1000000000,
                'current_price' => (int)($position['currentPrice']['units'] ?? 0) +
                    ($position['currentPrice']['nano'] ?? 0) / 1000000000,
                'currency' => $position['averagePositionPrice']['currency'],
            ]);
        return $position->toArray();
    }

    /**
     * Получение списка позиций в портфеле
     */
    public function getPositions(string $accountId): array
    {
        return Cache::remember("position_{$accountId}", now()->addHours(3), function () use ($accountId) {
            try {
                $responseData = $this->requestBuilder->makeRequest(
                    'tinkoff.public.invest.api.contract.v1.OperationsService/GetPositions',
                    ['accountId' => $accountId]
                );

                // Стандартизируем структуру ответа
                $positions = [];

                // Обрабатываем ценные бумаги
                foreach ($responseData['securities'] ?? [] as $security) {
                    $positions[] = [
                        'figi' => $security['figi'],
                        'ticker' => $security['ticker'] ?? null,
                        'instrument_type' => $security['instrumentType'],
                        'balance' => $security['balance'],
                        'position_uid' => $security['positionUid'] ?? null,
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
                        'average_price' => $future['averagePositionPrice']['units'] ?? null,
                        'expected_yield' => $future['expectedYield']['units'] ?? null,
                        'current_price' => $future['currentPrice']['units'] ?? null,
                        'currency' => $future['averagePositionPrice']['currency'] ?? 'RUB',
                        'type' => 'future'
                    ];
                }

                return $positions;

            } catch (GuzzleException $e) {
                throw new \RuntimeException('Tinkoff Invest API error: ' . $e->getMessage());
            }
        });
    }

    /**
     * Получение информации об инструменте по FIGI
     */
    public function getInstrumentByFigi(string $figi): array
    {
        return Cache::remember("instrument_{$figi}", now()->addHours(3), function () use ($figi) {
            try {
                $data = $this->requestBuilder->makeRequest(
                    'tinkoff.public.invest.api.contract.v1.InstrumentsService/GetInstrumentBy',
                    [
                        'idType' => 1, // 1 = FIGI
                        'id' => $figi,
                        'classCode' => '' // Можно указать класс инструмента или оставить пустым
                    ]
                );

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
        });
    }
}
