<?php

namespace App\Providers;

use App\Models\Appointment;
use Illuminate\Support\Facades\URL;
use App\Policies\AppointmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
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
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Paginator::defaultView('vendor.pagination.compact');
        URL::forceScheme('https');
    }
}
