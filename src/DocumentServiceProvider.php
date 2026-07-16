<?php

namespace Yoosuf\Document;

use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/document.php', 'document');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/document.php' => config_path('document.php'),
        ], 'document-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'document-migrations');

        if ((bool) config('document.load_routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
