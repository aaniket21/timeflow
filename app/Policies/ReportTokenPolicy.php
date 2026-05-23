<?php

namespace App\Policies;

use App\Models\ReportToken;
use App\Models\User;

class ReportTokenPolicy
{
    public function view(User $user, ReportToken $reportToken): bool
    {
        return $user->id === $reportToken->user_id;
    }

    public function update(User $user, ReportToken $reportToken): bool
    {
        return $user->id === $reportToken->user_id;
    }

    public function delete(User $user, ReportToken $reportToken): bool
    {
        return $user->id === $reportToken->user_id;
    }
}
