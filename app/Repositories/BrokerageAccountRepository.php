<?php

namespace App\Repositories;

use App\Models\BrokerageAccount;
use App\Repositories\Contracts\BrokerageAccountRepositoryInterface;

class BrokerageAccountRepository implements BrokerageAccountRepositoryInterface
{
    public function firstOrCreate(string $accountId, int $userId): array
    {
        $account = BrokerageAccount::firstOrCreate(
            ['account_id' => $accountId],
            ['user_id' => $userId]
        );

        return $account->toArray();
    }
}
