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
    
    public function register()
    {
        $this->app->bind('App\Services\IAdminService', 'App\Services\AdminService');
        $this->app->bind('App\Services\ISkillService', 'App\Services\SkillService');
    }
}