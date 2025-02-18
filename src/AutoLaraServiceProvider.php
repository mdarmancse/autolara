<?php

namespace Mdarmancse\AutoLara;

use Illuminate\Support\ServiceProvider;

class AutoLaraServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register bindings
        $this->app->bind(
            \Mdarmancse\AutoLara\Repositories\BaseRepositoryInterface::class,
            \Mdarmancse\AutoLara\Repositories\BaseRepository::class
        );
    }

    public function boot()
    {
        // Publish Config
        $this->publishes([
            __DIR__.'/../config/autolara.php' => config_path('autolara.php'),
        ], 'config');

        // Load Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
