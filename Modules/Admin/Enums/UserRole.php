<?php

declare(strict_types=1);

namespace Modules\Admin\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'purple',
            self::ADMIN => 'blue',
        };
    }
}