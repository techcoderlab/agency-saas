<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
{
    private function checkGates(User $user, string $permission, string $tokenScope): bool
    {
        // if($user->isNotSuperAdmin()){
        if (!in_array('forms', $user->tenant->enabled_modules ?? [])) {  // Super admin cant pass this check because it doesn't has tenant
            return false; 
        }
        // }  

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
        return $this->checkGates($user, 'view forms', 'forms:view');
    }

    public function view(User $user, Form $form): bool
    {
        return $user->tenant_id === $form->tenant_id
            && $this->checkGates($user, 'view forms', 'forms:view');
    }

    public function create(User $user): bool
    {
        return $this->checkGates($user, 'write forms', 'forms:write');
    }

    public function update(User $user, Form $form): bool
    {
        return $user->tenant_id === $form->tenant_id
            && $this->checkGates($user, 'update forms', 'forms:update');
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->tenant_id === $form->tenant_id
            && $this->checkGates($user, 'delete forms', 'forms:delete');
    }
}
