<?php

use App\Providers\AppServiceProvider;
use Modules\Admin\Providers\AdminServiceProvider;
use Modules\Authentication\Providers\AuthenticationServiceProvider;

return [
    AppServiceProvider::class,
    AuthenticationServiceProvider::class,
    AdminServiceProvider::class,
];