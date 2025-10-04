<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserObserver
{

    public function created(User $user): void
    {
        $this->logAction('created', $user);
    }

    public function updated(User $user): void
    {
        $this->logAction('updated', $user, json_encode($user->getChanges()));
    }

    public function deleted(User $user): void
    {
        $this->logAction('deleted', $user);
    }

    /**
     * Helper function to log actions.
     *
     * @param  string       $action
     * @param  User         $user
     * @param  string|null  $details
     */
    private function logAction(string $action, User $user, ?string $details = null): void
    {
        if (Auth::check()) {
            AuditLog::create([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => 'user',
                'entity_id'   => $user->user_id,
                'details'     => $details,
                'created_at'  => now(),
            ]);
        }
    }

}
