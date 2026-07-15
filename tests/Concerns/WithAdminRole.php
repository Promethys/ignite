<?php

namespace Tests\Concerns;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait WithAdminRole
{
    protected function setUpAdminRole(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('admin');
    }

    protected function actingAsPanelUser($user)
    {
        return $this->withSession(['auth.password_confirmed_at' => time()])
            ->actingAs($user);
    }
}
