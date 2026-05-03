<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortalKitItemRequest;
use App\Http\Requests\UpdatePortalKitItemRequest;
use App\Models\AuditLog;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use App\Notifications\KitItemFlaggedForInspection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KitItemController extends Controller
{
    public function index(Request $request): View
    {
        $client = auth()->user()->client;
        $search = $request->string('search')->trim()->toString();
        $groupFilter = $request->string('group')->trim()->toString();

        if (! $client) {
            return view('portal.kit.index', [
                'kitItems' => collect(),
                'kitGroups' => collect(),
                'search' => $search,
                'groupFilter' => $groupFilter,
            ]);
        }

        $query = $client->kitItems()->with(['kitType', 'kitGroup']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('asset_tag', 'LIKE', "%{$search}%")
                    ->orWhere('serial_no', 'LIKE', "%{$search}%")
                    ->orWhere('custom_type_name', 'LIKE', "%{$search}%")
                    ->orWhere('manufacturer', 'LIKE', "%{$search}%")
                    ->orWhere('model', 'LIKE', "%{$search}%");
            });
        }

        if ($groupFilter === 'none') {
            $query->whereNull('kit_group_id');
        } elseif ($groupFilter !== '' && ctype_digit($groupFilter)) {
            $query->where('kit_group_id', (int) $groupFilter);
        }

        $kitItems = $query->latest('created_at')->paginate(25)->withQueryString();
        $kitGroups = $client->kitGroups()->orderBy('name')->get();

        return view('portal.kit.index', compact('kitItems', 'kitGroups', 'search', 'groupFilter'));
    }

    public function create(): View
    {
        $client = auth()->user()->client;
        $kitTypes = KitType::orderBy('name')->get();
        $categories = $kitTypes->pluck('category')->filter()->unique()->sort()->values();
        $brands = $kitTypes->pluck('brand')->filter()->unique()->sort()->values();
        $kitGroups = $client ? $client->kitGroups()->orderBy('name')->get() : collect();

        return view('portal.kit.create', compact('kitTypes', 'categories', 'brands', 'kitGroups'));
    }

    public function store(StorePortalKitItemRequest $request): RedirectResponse
    {
        $client = auth()->user()->client;
        $validated = $request->validated();
        $kitItem = null;

        DB::transaction(function () use ($client, &$validated, &$kitItem) {
            if (! empty($validated['new_group_name'])) {
                $group = $client->kitGroups()->create(['name' => $validated['new_group_name']]);
                $validated['kit_group_id'] = $group->id;
                AuditLog::record('created', 'KitGroup', $group->id, "Client created kit group {$group->name} via Add Equipment");
            }
            unset($validated['new_group_name']);

            $kitItem = $client->kitItems()->create(array_merge(
                $validated,
                ['pending_review' => true]
            ));
        });

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
        $kitItem->load(['kitType', 'kitGroup', 'inspections' => fn ($q) => $q->complete()->latest('inspection_date')]);
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

    public function edit(KitItem $kitItem): View
    {
        $this->authorize('manage-own-kit', $kitItem);

        $kitItem->load(['kitType', 'kitGroup']);
        $locked = $kitItem->hasInspections();
        $kitTypes = KitType::orderBy('category')->orderBy('name')->get();
        $kitGroups = $kitItem->client->kitGroups()->orderBy('name')->get();

        return view('portal.kit.edit', compact('kitItem', 'locked', 'kitTypes', 'kitGroups'));
    }

    public function update(UpdatePortalKitItemRequest $request, KitItem $kitItem): RedirectResponse
    {
        $this->authorize('manage-own-kit', $kitItem);

        $data = $kitItem->hasInspections()
            ? collect($request->validated())->except(KitItem::LOCKED_FIELDS_AFTER_INSPECTION)->all()
            : $request->validated();

        if (array_key_exists('kit_group_id', $data) && $data['kit_group_id'] === '') {
            $data['kit_group_id'] = null;
        }

        $kitItem->update($data);

        AuditLog::record(
            'updated',
            'KitItem',
            $kitItem->id,
            'Client updated kit item '.($kitItem->asset_tag ?? "#{$kitItem->id}"),
            ['changed_fields' => array_keys($kitItem->getChanges())]
        );

        return redirect()->route('portal.kit.show', $kitItem)
            ->with('success', 'Equipment details updated.');
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
