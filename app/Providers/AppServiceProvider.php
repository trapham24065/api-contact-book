<?php

namespace App\Providers;

use App\Models\Contact;
use App\Observers\ContactObserver;
use App\Policies\ContactPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    protected $policies
        = [
            Contact::class => ContactPolicy::class,
        ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Contact::observe(ContactObserver::class);
    }

}
