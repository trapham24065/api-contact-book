<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContactPolicy
{

    public function before(User $user, string $ability): bool|null
    {
        if ($user->role === 0) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->user_id === $contact->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->user_id === $contact->user_id;
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->user_id === $contact->user_id;
    }

}
