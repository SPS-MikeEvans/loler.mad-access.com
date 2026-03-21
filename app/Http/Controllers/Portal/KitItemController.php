<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortalKitItemRequest;
use App\Models\AuditLog;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitItemFlaggedForInspection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KitItemController extends Controller
{
    public function index(): View
    {
        $client = auth()->user()->client;
        $kitItems = $client
            ? $client->kitItems()->with('kitType')->latest('created_at')->get()
            : collect();

        return view('portal.kit.index', compact('kitItems'));
    }

    public function create(): View
    {
        $kitTypes = KitType::orderBy('name')->get();

        return view('portal.kit.create', compact('kitTypes'));
    }

    public function store(StorePortalKitItemRequest $request): RedirectResponse
    {
        $client = auth()->user()->client;
        $kitItem = $client->kitItems()->create(array_merge(
            $request->validated(),
            ['pending_review' => true]
        ));

        AuditLog::record('created', 'KitItem', $kitItem->id, "Client submitted new item: {$kitItem->asset_tag}");

        return redirect()->route('portal.kit.index')
            ->with('success', 'Equipment submitted. Our team will review and activate it shortly.');
    }

    public function show(KitItem $kitItem): View
    {
        $this->authorize('manage-own-kit', $kitItem);
        $kitItem->load(['kitType', 'inspections' => fn ($q) => $q->complete()->latest('inspection_date')]);

        return view('portal.kit.show', compact('kitItem'));
    }

    public function flag(KitItem $kitItem, Request $request): RedirectResponse
    {
        $this->authorize('manage-own-kit', $kitItem);

        $validated = $request->validate([
            'flag_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $nowFlagged = ! $kitItem->flagged_for_inspection;

        $kitItem->update([
            'flagged_for_inspection' => $nowFlagged,
            'flag_notes' => $nowFlagged ? ($validated['flag_notes'] ?? null) : null,
        ]);

        if ($nowFlagged) {
            AuditLog::record('updated', 'KitItem', $kitItem->id, "Client flagged item {$kitItem->asset_tag} for inspection");

            User::query()->whereIn('role', ['admin', 'inspector'])->each(
                fn (User $u) => $u->notify(new KitItemFlaggedForInspection($kitItem, $validated['flag_notes'] ?? ''))
            );
        } else {
            AuditLog::record('updated', 'KitItem', $kitItem->id, "Client un-flagged item {$kitItem->asset_tag}");
        }

        $message = $nowFlagged
            ? 'Item flagged for inspection. Our team has been notified.'
            : 'Inspection flag removed.';

        return redirect()->route('portal.kit.show', $kitItem)->with('success', $message);
    }

    public function retire(KitItem $kitItem): RedirectResponse
    {
        $this->authorize('manage-own-kit', $kitItem);

        $kitItem->update([
            'status' => 'retired',
            'flagged_for_inspection' => false,
            'pending_review' => false,
        ]);

        AuditLog::record('updated', 'KitItem', $kitItem->id, "Client retired item {$kitItem->asset_tag}");

        return redirect()->route('portal.kit.index')->with('success', 'Item marked as retired.');
    }
}
