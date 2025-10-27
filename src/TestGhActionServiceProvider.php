<?php

namespace Mortezamasumi\TestGhAction;

use Illuminate\Support\ServiceProvider;

class TestGhActionServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot(): void
    {
        dump('boot test gh actions \n');
        // add some
    }
}
