<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Macros\DuskMacros;
use App\Macros\HttpMacros;

class DuskMacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 注册Dusk宏
        DuskMacros::register();

        // 注册HTTP宏
        HttpMacros::register();
    }
}
