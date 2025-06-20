<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TinkoffInvestService;
use Illuminate\Http\JsonResponse;

class InvestController extends Controller
{
    protected TinkoffInvestService $investService;

    public function __construct(TinkoffInvestService $investService)
    {
        $this->investService = $investService;
    }

    /**
     * Получение всех счетов пользователя
     */
    public function accounts(): JsonResponse
    {
        try {
            $accounts = $this->investService->getAccounts();

            return response()->json([
                'success' => true,
                'data' => $accounts
            ]);

        } catch (\RuntimeException $e) {
            $errorMessage = $e->getMessage();

            // Анализ ошибки
            if (str_contains($errorMessage, '400 Bad Request')) {
                $errorMessage = 'Invalid request format or parameters';
            } elseif (str_contains($errorMessage, '401')) {
                $errorMessage = 'Authentication failed. Check your API token';
            }

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
                'suggestion' => 'Check your API token and request format'
            ], 400);
        }
    }

    /**
     * Получение портфеля по accountId
     */
    public function portfolio(string $accountId): JsonResponse
    {
        try {
            $portfolio = $this->investService->getPortfolio($accountId);
            return response()->json($portfolio);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение позиций с детализацией по инструментам
     */
    public function positions(string $accountId): JsonResponse
    {
        try {
            $positions = $this->investService->getPositions($accountId);

            // Добавляем информацию об инструментах
            foreach ($positions as &$position) {
                if (!empty($position['figi'])) {
                    $instrument = $this->investService->getInstrumentByFigi($position['figi']);
                    $position['instrument'] = $instrument['payload'] ?? null;
                }
            }

            return response()->json($positions);
        } catch (\RuntimeException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
