<?php

namespace App\Repositories\Contracts;

interface BrokerageAccountRepositoryInterface
{
    public function firstOrCreate(string $accountId, int $userId): array;
}
