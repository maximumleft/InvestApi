<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Tinkoff\TinkoffInvestService;
use Illuminate\Console\Command;

class TinkoffPortfolioSyncCommand extends Command
{
    protected $signature = 'sync:tinkoff-portfolio';
    protected $description = 'Command description';

    public function __construct(
        private readonly TinkoffInvestService $investService
    ) {
        parent::__construct();
    }

    public function handle()
    {
//        $users = User::all();
//        foreach ($users as $user) {
//            $this->investService->getAccounts()
//        }
    }
}
