<?php

namespace App\Providers;

use App\Events\JobStatusChanged;
use App\Listeners\NotifyClientOfJobStatusChange;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\KitGroup;
use App\Models\KitItem;
use App\Models\Reconciliation;
use App\Models\User;
use App\Services\BankFeed\BankFeedProvider;
use App\Services\BankFeed\GoCardlessBankFeedProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BankFeedProvider::class, GoCardlessBankFeedProvider::class);
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            Reconciliation::TYPE_INVOICE => Invoice::class,
            Reconciliation::TYPE_EXPENSE => Expense::class,
        ]);

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
