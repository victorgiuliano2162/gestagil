<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Vite::prefetch(concurrency: 3);

        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection instanceof SQLiteConnection) {
                $event->connection->getPdo()->sqliteCreateFunction('unaccent', function (?string $string) {
                    return $string !== null ? Str::ascii($string) : null;
                });
            }
        });
    }
}
