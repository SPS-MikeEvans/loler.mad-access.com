<?php

namespace App\Providers;

use App\Events\JobStatusChanged;
use App\Listeners\NotifyClientOfJobStatusChange;
use App\Models\KitGroup;
use App\Models\KitItem;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('destructive-actions', fn (Request $request) => [
            Limit::perHour(5)->by((string) ($request->user()?->id ?? $request->ip())),
        ]);

        Gate::define('manage-own-kit', fn (User $u, KitItem $item) => $u->isClientViewer() && $u->client_id === $item->client_id);
        Gate::define('manage-own-kit-group', fn (User $u, KitGroup $group) => $u->isClientViewer() && $u->client_id === $group->client_id);
        Gate::define('manage-users', fn (User $u) => $u->isAdmin());
        Gate::define('edit-inspection-cost', fn (User $u) => $u->isAdmin());
        Gate::define('view-reports', fn (User $u) => $u->isAdmin() || $u->isInspector());
        Gate::define('view-audit-log', fn (User $u) => $u->isAdmin());
        Gate::define('view-all-inspections', fn (User $u) => $u->isAdmin());

        Event::listen(JobStatusChanged::class, NotifyClientOfJobStatusChange::class);
    }
}
