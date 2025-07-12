<?php

namespace Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mortezamasumi\CopyDb\CopyDbServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use LazilyRefreshDatabase;

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations()
    {
        /** @var OrchestraTestCase $this */
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            CopyDbServiceProvider::class,
        ];
    }
}
