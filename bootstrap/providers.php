<?php

use App\Providers\AppServiceProvider;
use Modules\Admin\Providers\AdminServiceProvider;
use Modules\Authentication\Providers\AuthenticationServiceProvider;
use Modules\Driver\Providers\DriverServiceProvider;
use Modules\Vehicle\Providers\VehicleServiceProvider;
use Modules\VehicleRequest\Providers\VehicleRequestServiceProvider;
use Modules\ApprovingOfficer\Providers\ApprovingOfficerServiceProvider;
use Modules\Settings\Providers\SettingsServiceProvider;
use Modules\Maintenance\Providers\MaintenanceServiceProvider;
use Modules\VehicleReport\Providers\VehicleReportServiceProvider;
use Modules\Destination\Providers\DestinationServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
    AdminServiceProvider::class,
    DriverServiceProvider::class,
    VehicleServiceProvider::class,
    VehicleRequestServiceProvider::class,
    ApprovingOfficerServiceProvider::class,
    SettingsServiceProvider::class,
    MaintenanceServiceProvider::class,
    VehicleReportServiceProvider::class,
    DestinationServiceProvider::class,
];
