<?php
namespace P4ndish\SantimPay;
use Illuminate\Support\ServiceProvider;

class SantimPayServiceProvider extends ServiceProvider
{
    public function boot(): void {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/santimpay.php' => config_path('santimpay.php'),
            ], 'config');
        }
    }
    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../config/santimpay.php', 'santimpay');
        $this->app->singleton('santimpay', fn() => new SantimPay());
    }
}