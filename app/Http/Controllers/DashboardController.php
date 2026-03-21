<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\KitItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        if (auth()->user()->isClientViewer()) {
            return redirect()->route('portal.dashboard');
        }

        $metrics = null;

        if (auth()->user()->isAdmin()) {
            $metrics = [
                'overdue' => KitItem::whereNotNull('next_inspection_due')->where('next_inspection_due', '<', today())->count(),
                'due_soon' => KitItem::whereBetween('next_inspection_due', [today(), today()->addDays(30)])->count(),
                'inspectors' => User::where('role', 'inspector')->count(),
                'revenue_ytd' => Inspection::whereYear('inspection_date', now()->year)->sum('cost'),
                'expiring_certs' => User::where('role', 'inspector')
                    ->whereBetween('qualification_expiry', [today(), today()->addDays(30)])
                    ->count(),
                'flagged_items' => KitItem::flaggedForInspection()->with('client', 'kitType')->get(),
                'pending_items' => KitItem::clientPending()->with('client', 'kitType')->get(),
            ];
        }

        return view('dashboard', compact('metrics'));
    }
}
