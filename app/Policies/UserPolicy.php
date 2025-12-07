<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private function checkGates(User $user, string $permission, string $tokenScope): bool
    {
        if (!in_array('users', $user->tenant->enabled_modules ?? [])) {  // Super admin cant pass this check because it doesn't has tenant
            return false;
        }

        if (!$user->can($permission)) {
            return false;
        }

        if (!$user->tokenCan($tokenScope)) {
            return false;
        }

        return true;
    }

    public function viewAny(User $user): bool
    {
        return $this->checkGates($user, 'view users', 'users:view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->tenant_id === $target->tenant_id
            && $this->checkGates($user, 'view users', 'users:view');
    }

    public function create(User $user): bool
    {
        return $this->checkGates($user, 'write users', 'users:write');
    }

    public function update(User $user, User $target): bool
    {
        return $user->tenant_id === $target->tenant_id
            && $this->checkGates($user, 'update users', 'users:update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->tenant_id === $target->tenant_id
            && $this->checkGates($user, 'delete users', 'users:delete');
    }
}
