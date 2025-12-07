<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * The 3-Gate Check Helper
     */
    private function checkGates(User $user, string $permission, string $tokenScope): bool
    {
        // Gate 1: Tenant Module Check
        // (Middleware handles the route, but Policy handles the specifics if called manually)
        if (!in_array('leads', $user->tenant->enabled_modules ?? [])) {  // Super admin cant pass this check because it doesn't has tenant
            return false;
        }

        // Gate 2: User Role Check (Spatie)
        // Ensure you have seeded permissions like 'read leads', 'write leads'
        if (!$user->can($permission)) {
             return false;
        }

        // Gate 3: Token Scope Check (Sanctum)
        // Works for API Keys. Web sessions automatically pass tokenCan().
        if (!$user->tokenCan($tokenScope)) {
            return false;
        }

        return true;
    }

    public function viewAny(User $user): bool
    {
        return $this->checkGates($user, 'view leads', 'leads:view');
    }

    public function view(User $user, Lead $lead): bool
    {
        // Also check if lead belongs to tenant
        return $user->tenant_id === $lead->tenant_id 
            && $this->checkGates($user, 'view leads', 'leads:view');
    }

    public function create(User $user): bool
    {
        return $this->checkGates($user, 'write leads', 'leads:write');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id 
            && $this->checkGates($user, 'update leads', 'leads:update');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id 
            && $this->checkGates($user, 'delete leads', 'leads:delete');
    }
}