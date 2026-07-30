<?php

use App\Providers\AppServiceProvider;
use App\Providers\DatabaseMonitoringServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MailServiceProvider;
use App\Providers\McpServiceProvider;
use App\Providers\PasswordServiceProvider;
use App\Providers\RequestsServiceProvider;

return [
    AppServiceProvider::class,
    DatabaseMonitoringServiceProvider::class,
    AdminPanelProvider::class,
    FortifyServiceProvider::class,
    McpServiceProvider::class,
    PasswordServiceProvider::class,
    MailServiceProvider::class,
    RequestsServiceProvider::class,
];
