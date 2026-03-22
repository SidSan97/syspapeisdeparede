<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\Order;
use App\Models\BudgetWall;
use App\Models\LayoutCardHistory;
use App\Models\MyFavoriteCollectionImage;
use App\Models\OrderBudget;
use App\Models\RequestLayoutArt;
use App\Models\RequestLayoutArtInteraction;
use App\Observers\BudgetObserver;
use App\Observers\OrderObserver;
use App\Observers\TenantObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('settings')) {
            $settings = Cache::rememberForever('tiny_erp_settings', function () {
                return DB::table('settings')->pluck('val', 'name')->toArray();
            });
    
            config()->set('app.tiny_erp_settings', $settings);
        }

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super admin') ? true : null;
        });

        \Carbon\Carbon::setLocale($this->app->getLocale());

        // Register Tenant Observer for models that need tenant isolation
        Budget::observe([TenantObserver::class, BudgetObserver::class]);
        BudgetRoom::observe(TenantObserver::class);
        BudgetWall::observe(TenantObserver::class);
        OrderBudget::observe(TenantObserver::class);
        RequestLayoutArt::observe(TenantObserver::class);
        RequestLayoutArtInteraction::observe(TenantObserver::class);
        MyFavoriteCollectionImage::observe(TenantObserver::class);
        LayoutCardHistory::observe(TenantObserver::class);

        Order::observe(OrderObserver::class);
    }
}
