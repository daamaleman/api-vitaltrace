<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * The system roles, matching the roles table (section 2.1).
     *
     * @var array<int, string>
     */
    public const ROLES = [
        'PATIENT',
        'RELATIVE',
        'DOCTOR',
        'NURSE',
        'ADMISSION',
        'SYSTEM_ADMIN',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        foreach (self::ROLES as $role) {
            Gate::define("role:{$role}", static fn(User $user): bool => $user->hasRole($role));
        }
    }
}
