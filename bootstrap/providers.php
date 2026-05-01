<?php

use App\Providers\AppServiceProvider;
use App\Providers\DatabaseMonitoringServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    DatabaseMonitoringServiceProvider::class,
    FortifyServiceProvider::class,
];
