<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Services\TenantManager;

class LeadPolicy
{
    private function checkAccess(User $user, string $permission, string $scope): bool
    {
        if ($user->isSuperAdmin()) return true;

        $tm = app(TenantManager::class);

        return !is_null($user->current_tenant_id)
            && $tm->isModuleEnabled('leads')
            && $user->can($permission)
            && $user->tokenCan($scope);
    }

    public function viewAny(User $user): bool
    {
        return $this->checkAccess($user, 'view leads', 'leads:view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->current_tenant_id === $lead->tenant_id
            && $this->checkAccess($user, 'view leads', 'leads:view');
    }

    public function create(User $user): bool
    {
        return $this->checkAccess($user, 'write leads', 'leads:write');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->current_tenant_id === $lead->tenant_id
            && $this->checkAccess($user, 'update leads', 'leads:update');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->current_tenant_id === $lead->tenant_id
            && $this->checkAccess($user, 'delete leads', 'leads:delete');
    }
}
