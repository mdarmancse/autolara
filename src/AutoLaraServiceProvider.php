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
        //
    }
}
