<?php

namespace App\Policies;

use App\Models\AiChat;
use App\Models\User;

class AiChatPolicy
{
    private function checkGates(User $user, string $permission, string $tokenScope): bool
    {
        if (!in_array('ai_chats', $user->tenant->enabled_modules ?? [])) {  // Super admin cant pass this check because it doesn't has tenant
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
        return $this->checkGates($user, 'view ai_chats', 'ai_chats:view');
    }

    public function view(User $user, AiChat $chat): bool
    {
        return $user->tenant_id === $chat->tenant_id
            && $this->checkGates($user, 'view ai_chats', 'ai_chats:view');
    }

    public function create(User $user): bool
    {
        return $this->checkGates($user, 'write ai_chats', 'ai_chats:write');
    }

    public function update(User $user, AiChat $chat): bool
    {
        return $user->tenant_id === $chat->tenant_id
            && $this->checkGates($user, 'update ai_chats', 'ai_chats:update');
    }

    public function delete(User $user, AiChat $chat): bool
    {
        return $user->tenant_id === $chat->tenant_id
            && $this->checkGates($user, 'delete ai_chats', 'ai_chats:delete');
    }
}
