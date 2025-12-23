<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // The 'CheckTenantModule' middleware already verifies if the 'leads' module is enabled.
        // The 'BelongsToTenant' global scope on the Lead model handles data isolation.
        // This policy just needs to check if the user has the role permission to view leads
        // within their established tenant context.
        return $user->hasPermissionTo('view leads') && !is_null($user->current_tenant_id);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lead $lead): bool
    {
        // Check if the lead belongs to the user's active tenant and if they have permission.
        return $user->current_tenant_id === $lead->tenant_id && $user->hasPermissionTo('view leads');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // The 'CheckTenantModule' middleware handles module access.
        // We just check for role permission and tenant context.
        return $user->hasPermissionTo('write leads') && !is_null($user->current_tenant_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $user->current_tenant_id === $lead->tenant_id && $user->hasPermissionTo('update leads');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $user->current_tenant_id === $lead->tenant_id && $user->hasPermissionTo('delete leads');
    }
}
