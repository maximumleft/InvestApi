<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Tinkoff\TinkoffInvestService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected TinkoffInvestService $investService;

    public function __construct(TinkoffInvestService $investService)
    {
        $this->investService = $investService;
    }

    /**
     * Получение всех счетов пользователя
     */

}
