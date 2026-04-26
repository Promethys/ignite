<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class DatabaseMonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        DB::listen(function (QueryExecuted $query) {
            $method = strtoupper(Request::method());
            $uri = Request::path();

            Log::channel('sql')->info($query->sql, [
                'method' => $method,
                'uri' => $uri,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'raw_sql' => $query->toRawSql(),
            ]);
        });
    }
}
