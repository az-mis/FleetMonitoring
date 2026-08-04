<?php

use App\Providers\AppServiceProvider;
use Modules\Admin\Providers\AdminServiceProvider;
use Modules\Authentication\Providers\AuthenticationServiceProvider;
use Modules\Vehicle\Providers\VehicleServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
    AdminServiceProvider::class,
    VehicleServiceProvider::class,
];