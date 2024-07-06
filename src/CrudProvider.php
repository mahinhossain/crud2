<?php

namespace Mahin\Crud;

use Illuminate\Support\ServiceProvider;
use Mahin\Crud\Console\Commands\CrudCommand;

class CrudProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->commands([
            CrudCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../resources/stubs' => resource_path('stubs'),
        ], 'crud-generator-stubs');
        
    }
}
