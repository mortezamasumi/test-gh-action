<?php

namespace Mortezamasumi\CopyDb;

use Illuminate\Support\ServiceProvider;
use Mortezamasumi\CopyDb\Console\Copy;
use Mortezamasumi\CopyDb\Console\CopyOld;

class CopyDbServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Copy::class,
                CopyOld::class,
            ]);
        }
    }
}
