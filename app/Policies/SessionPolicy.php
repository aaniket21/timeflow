<?php

namespace App\Policies;

use App\Models\TimeSession;
use App\Models\User;

class SessionPolicy
{
    public function view(User $user, TimeSession $session): bool
    {
        return $user->id === $session->user_id;
    }

    public function update(User $user, TimeSession $session): bool
    {
        return $user->id === $session->user_id;
    }

    public function delete(User $user, TimeSession $session): bool
    {
        return $user->id === $session->user_id;
    }
}
