<?php

namespace Mortezamasumi\TestGhAction\Tests;

use Mortezamasumi\TestGhAction\TestGhActionServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TestGhActionServiceProvider::class,
        ];
    }
}
