<?php

namespace App\Providers;

use App\Repositories\BrokerageAccountRepository;
use App\Repositories\Contracts\BrokerageAccountRepositoryInterface;
use App\Repositories\Contracts\PositionRepositoryInterface;
use App\Repositories\PositionRepository;
use App\Services\Tinkoff\TinkoffInvestService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            BrokerageAccountRepositoryInterface::class,
            BrokerageAccountRepository::class
        );
        $this->app->bind(
            PositionRepositoryInterface::class,
            PositionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
