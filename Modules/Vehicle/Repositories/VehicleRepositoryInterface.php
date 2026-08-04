<?php

declare(strict_types=1);

namespace Modules\Vehicle\Repositories;

use Modules\Vehicle\Models\Vehicle;

interface VehicleRepositoryInterface
{
    public function create(array $data): Vehicle;
}
