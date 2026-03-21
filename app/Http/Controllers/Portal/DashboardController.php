<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $client = auth()->user()->client;

        $overdue = 0;
        $dueSoon = 0;
        $flagged = 0;
        $pending = 0;

        if ($client) {
            $overdue = $client->kitItems()->whereNotNull('next_inspection_due')->where('next_inspection_due', '<', today())->whereNotIn('status', ['retired'])->count();
            $dueSoon = $client->kitItems()->whereBetween('next_inspection_due', [today(), today()->addDays(30)])->whereNotIn('status', ['retired'])->count();
            $flagged = $client->kitItems()->where('flagged_for_inspection', true)->count();
            $pending = $client->kitItems()->where('pending_review', true)->count();
        }

        return view('portal.dashboard', compact('client', 'overdue', 'dueSoon', 'flagged', 'pending'));
    }
}
