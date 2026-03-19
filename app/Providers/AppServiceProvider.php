<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-users', fn(User $u) => $u->isAdmin());
        Gate::define('edit-inspection-cost', fn(User $u) => $u->isAdmin());
        Gate::define('view-reports', fn(User $u) => $u->isAdmin() || $u->isInspector());
        Gate::define('view-audit-log', fn(User $u) => $u->isAdmin());
        Gate::define('view-all-inspections', fn(User $u) => $u->isAdmin());
    }
}
