<?php

namespace App\Enums;

enum RoleLevel: string
{
    case Free = "free";
    case Premium = "premium";

    case Enterprise = "enterprise";

    case Admin = "admin";

    public function maxRequestPerHour(): int
    {
        return match ($this) {
            self::Free => 10,
            self::Premium => 1000,
            self::Enterprise => 10000,
            self::Admin => 999999999,
        };
    }
}
