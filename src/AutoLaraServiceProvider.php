<?php

namespace Mdarmancse\AutoLara;

use Illuminate\Support\ServiceProvider;
use Mdarmancse\AutoLara\Commands\GenerateCrudCommand;

class AutoLaraServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            GenerateCrudCommand::class,
        ]);
    }

    public function boot()
    {
        // Load package routes
        $this->loadRoutesFrom(__DIR__ . '/../src/routes/web.php');

        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/autolara.php' => config_path('autolara.php'),
        ], 'config');
    }
}
