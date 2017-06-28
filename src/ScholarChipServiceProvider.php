<?php

namespace Itacs\ScholarChip;

use SoapClient;
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
        $this->loadRoutesFrom(__DIR__.'/routes.php');

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
                            new SoapClient(
                                    config('scholarchip.wsdl_url'),
                                    array('user_agent'=>'')
                                ),
                            config('scholarchip')
                        );
        });
    }
}
