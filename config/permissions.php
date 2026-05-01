<?php

declare(strict_types=1);

function getRoleLevel(string $role): int
{
    return match ($role) {
        'admin'   => 4,
        'gestore' => 3,
        'master'  => 2,
        'guida'   => 1,
        default   => 0,
    };
}

function hasRole(array $user, string $required): bool
{
    $userRole = (string) ($user['role'] ?? 'user');

    return getRoleLevel($userRole) >= getRoleLevel($required);
}