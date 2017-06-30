<?php

namespace Itacs\ScholarChip;

use Illuminate\Support\ServiceProvider;
use Itacs\ScholarChip\ScholarChip;

class ScholarChipServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Config/scholarchip.php' => config_path('scholarchip.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Itacs\ScholarChip\ScholarChip', function ($app) {
            return new ScholarChip(
                            config('scholarchip.user'),
                            config('scholarchip.password'),
                            config('scholarchip.gl'),
                            config('scholarchip.wsdl_url')
                        );
        });
    }
}
