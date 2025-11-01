<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['view']->addNamespace('theme', public_path() . '/theme');
        if (defined('isWEBMAN') && isWEBMAN) {
            app()->terminating(function () {
                try {
                    while (DB::transactionLevel() > 0) DB::rollBack();
                } catch (\Throwable $e) {}
                DB::disconnect();
            });
        }
    }
}
