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
        $categories = $kitTypes->pluck('category')->filter()->unique()->sort()->values();
        $brands = $kitTypes->pluck('brand')->filter()->unique()->sort()->values();

        return view('portal.kit.create', compact('kitTypes', 'categories', 'brands'));
    }

    public function store(StorePortalKitItemRequest $request): RedirectResponse
    {
        $client = auth()->user()->client;
        $kitItem = $client->kitItems()->create(array_merge(
            $request->validated(),
            ['pending_review' => true]
        ));

        if ($kitItem->isCustomType()) {
            $similar = KitType::whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($kitItem->custom_type_name).'%'])->first();
            $note = $similar
                ? "Client submitted custom item \"{$kitItem->custom_type_name}\" — possible match: {$similar->name} (ID {$similar->id})"
                : "Client submitted custom item: {$kitItem->custom_type_name}";
            AuditLog::record('created', 'KitItem', $kitItem->id, $note);
        } else {
            AuditLog::record('created', 'KitItem', $kitItem->id, "Client submitted new item: {$kitItem->asset_tag}");
        }

        return redirect()->route('portal.kit.index')
            ->with('success', 'Equipment submitted. Our team will review and activate it shortly.');
    }

    public function updateCustomName(KitItem $kitItem, Request $request): RedirectResponse
    {
        $this->authorize('manage-own-kit', $kitItem);

        abort_unless($kitItem->isCustomType() && $kitItem->pending_review, 403);

        $validated = $request->validate([
            'custom_type_name' => ['required', 'string', 'max:100'],
        ]);

        $kitItem->update($validated);
        AuditLog::record('updated', 'KitItem', $kitItem->id, "Client corrected custom type name to: {$validated['custom_type_name']}");

        return redirect()->route('portal.kit.show', $kitItem)->with('success', 'Equipment name updated.');
    }

    public function show(KitItem $kitItem): View
    {
        $this->authorize('manage-own-kit', $kitItem);
        $kitItem->load(['kitType', 'inspections' => fn ($q) => $q->complete()->latest('inspection_date')]);
        $retireConfirmation = ! $kitItem->pending_review && $kitItem->status !== 'retired'
            ? $this->issueConfirmedAction(
                'retire.kit-item',
                'KitItem',
                $kitItem->id,
                "RETIRE-ITEM-{$kitItem->id}"
            )
            : null;

        return view('portal.kit.show', compact('kitItem', 'retireConfirmation'));
    }

    public function storeFlag(KitItem $kitItem, Request $request): RedirectResponse
    {
        $this->authorize('manage-own-kit', $kitItem);

        $validated = $request->validate([
            'flag_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $kitItem->update([
            'flagged_for_inspection' => true,
            'flag_notes' => $validated['flag_notes'] ?? null,
        ]);

        AuditLog::record('updated', 'KitItem', $kitItem->id, "Client flagged item {$kitItem->asset_tag} for inspection");

        User::query()->whereIn('role', ['admin', 'inspector'])->each(
            fn (User $u) => $u->notify(new KitItemFlaggedForInspection($kitItem, $validated['flag_notes'] ?? ''))
        );

        return redirect()->route('portal.kit.show', $kitItem)->with('success', 'Item flagged for inspection. Our team has been notified.');
    }

    public function destroyFlag(KitItem $kitItem): RedirectResponse
    {
        $this->authorize('manage-own-kit', $kitItem);

        $kitItem->update([
            'flagged_for_inspection' => false,
            'flag_notes' => null,
        ]);

        AuditLog::record('updated', 'KitItem', $kitItem->id, "Client removed inspection flag from item {$kitItem->asset_tag}");

        return redirect()->route('portal.kit.show', $kitItem)->with('success', 'Inspection flag removed.');
    }

    public function retire(Request $request, KitItem $kitItem): RedirectResponse
    {
        $this->authorize('manage-own-kit', $kitItem);

        $confirmation = $this->makeConfirmedAction(
            'retire.kit-item',
            'KitItem',
            $kitItem->id,
            "RETIRE-ITEM-{$kitItem->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        $kitItem->update([
            'status' => 'retired',
            'flagged_for_inspection' => false,
            'pending_review' => false,
        ]);

        AuditLog::record(
            'updated',
            'KitItem',
            $kitItem->id,
            "Client retired item {$kitItem->asset_tag}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        return redirect()->route('portal.kit.index')->with('success', 'Item marked as retired.');
    }
}
