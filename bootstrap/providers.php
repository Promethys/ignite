<?php

use App\Providers\AppServiceProvider;
use App\Providers\DatabaseMonitoringServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    DatabaseMonitoringServiceProvider::class,
    AdminPanelProvider::class,
    FortifyServiceProvider::class,
];
