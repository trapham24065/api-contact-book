<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;

class ContactObserver
{

    public function created(Contact $contact): void
    {
        $this->logAction('created', $contact);
    }

    public function updated(Contact $contact): void
    {
        $this->logAction('updated', $contact);
    }

    public function deleted(Contact $contact): void
    {
        $this->logAction('deleted', $contact);
    }

    private function logAction(string $action, Contact $contact): void
    {
        if (Auth::check()) {
            AuditLog::create([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => 'contact',
                'entity_id'   => $contact->contact_id,
                'details'     => json_encode($contact->getChanges(), JSON_THROW_ON_ERROR),
                'created_at'  => now(),
            ]);
        }
    }

}
