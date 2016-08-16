<?php

namespace Laravolt\IndonesiaLogo;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {

        $this->commands(\Laravolt\IndonesiaLogo\Commands\CrawlCommand::class);
        $this->commands(\Laravolt\IndonesiaLogo\Commands\SeedCommand::class);
    }

    public function boot()
    {
        $this->publishes([
	        __DIR__ . '/migrations' => $this->app->databasePath() . '/migrations'
	    ], 'migrations');


    }
}