<?php

namespace App\Providers;

use App\Models\Contact;
use App\Models\User;
use App\Observers\ContactObserver;
use App\Observers\UserObserver;
use App\Policies\ContactPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    protected $policies
        = [
            Contact::class => ContactPolicy::class,
            User::class    => UserPolicy::class,
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
        User::observe(UserObserver::class);
    }

}
