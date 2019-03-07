<?php

namespace Coderello\PopulatedFactory\Providers;

use Coderello\PopulatedFactory\Commands\PopulatedFactoryMake;
use Illuminate\Support\ServiceProvider;

class PopulatedFactoryServiceProvider extends ServiceProvider
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
        if ($this->app->runningInConsole()) {
            $this->commands([
                PopulatedFactoryMake::class,
            ]);
        }
    }
}