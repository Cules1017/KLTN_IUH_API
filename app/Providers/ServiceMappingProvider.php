<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceMappingProvider extends ServiceProvider
{

    /**
     * Boot the services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $aMapping = [
            
        ];
        foreach ($aMapping as $key => $value) {
            $this->app->singleton('App\Services\\' . $key . '\I' . $value . 'Service', 'App\Services\\' . $key . '\\' . $value . 'Service');
        }
    }
}