<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryMappingProvider extends ServiceProvider
{
    /**
     * Boot the repository for the application.
     *
     * @return void
     */
    public function boot()
    {
        $aMapping = [
           
        ];
        foreach ($aMapping as $key => $value) {
            $this->app->singleton('App\Repositories\\' . $key . '\I' . $value . 'Repository', 'App\Repositories\\' . $key . '\\' . $value . 'Repository');
        }
    }
}