<?php

namespace App\Policies;

use App\Models\Webhook;
use App\Models\User;

class WebhookPolicy
{
    private function checkGates(User $user, string $permission, string $tokenScope): bool
    {
        if (!in_array('webhooks', $user->tenant->enabled_modules ?? [])) {  // Super admin cant pass this check because it doesn't has tenant
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
        return $this->checkGates($user, 'view webhooks', 'webhooks:view');
    }

    public function view(User $user, Webhook $wh): bool
    {
        return $user->tenant_id === $wh->tenant_id
            && $this->checkGates($user, 'view webhooks', 'webhooks:view');
    }

    public function create(User $user): bool
    {
        return $this->checkGates($user, 'write webhooks', 'webhooks:write');
    }

    public function update(User $user, Webhook $wh): bool
    {
        return $user->tenant_id === $wh->tenant_id
            && $this->checkGates($user, 'update webhooks', 'webhooks:update');
    }

    public function delete(User $user, Webhook $wh): bool
    {
        return $user->tenant_id === $wh->tenant_id
            && $this->checkGates($user, 'delete webhooks', 'webhooks:delete');
    }
}
