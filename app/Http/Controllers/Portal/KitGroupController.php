<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortalKitGroupRequest;
use App\Http\Requests\UpdatePortalKitGroupRequest;
use App\Models\AuditLog;
use App\Models\KitGroup;
use App\Models\KitItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KitGroupController extends Controller
{
    public function index(Request $request): View
    {
        $client = auth()->user()->client;
        $search = $request->string('search')->trim()->toString();

        $query = $client
            ? $client->kitGroups()->withCount('kitItems')
            : KitGroup::query()->whereRaw('1 = 0');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $kitGroups = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('portal.kit-groups.index', compact('kitGroups', 'search'));
    }

    public function create(): View
    {
        $client = auth()->user()->client;
        $assignableItems = $client
            ? $client->kitItems()
                ->whereNull('kit_group_id')
                ->whereNotIn('status', ['retired'])
                ->with('kitType')
                ->orderBy('id')
                ->get()
            : collect();

        return view('portal.kit-groups.create', compact('assignableItems'));
    }

    public function store(StorePortalKitGroupRequest $request): RedirectResponse
    {
        $client = auth()->user()->client;
        $data = $request->validated();
        $itemIds = $data['kit_item_ids'] ?? [];

        $group = DB::transaction(function () use ($client, $data, $itemIds) {
            $group = $client->kitGroups()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            if (! empty($itemIds)) {
                KitItem::whereIn('id', $itemIds)
                    ->where('client_id', $client->id)
                    ->update(['kit_group_id' => $group->id]);
            }

            return $group;
        });

        AuditLog::record(
            'created',
            'KitGroup',
            $group->id,
            "Client created kit group {$group->name}",
            ['kit_item_ids_attached' => array_values($itemIds)]
        );

        return redirect()->route('portal.kit-groups.show', $group)
            ->with('success', 'Kit group created.');
    }

    public function show(KitGroup $kitGroup): View
    {
        $this->authorize('manage-own-kit-group', $kitGroup);

        $kitGroup->load(['kitItems' => fn ($q) => $q->with('kitType')->orderBy('id')]);

        return view('portal.kit-groups.show', compact('kitGroup'));
    }

    public function edit(KitGroup $kitGroup): View
    {
        $this->authorize('manage-own-kit-group', $kitGroup);

        $client = auth()->user()->client;
        $assignableItems = $client->kitItems()
            ->where(function ($q) use ($kitGroup) {
                $q->whereNull('kit_group_id')
                    ->orWhere('kit_group_id', $kitGroup->id);
            })
            ->whereNotIn('status', ['retired'])
            ->with('kitType')
            ->orderBy('id')
            ->get();

        $deleteConfirmation = $this->issueConfirmedAction(
            'delete.kit-group',
            'KitGroup',
            $kitGroup->id,
            "DELETE-GROUP-{$kitGroup->id}"
        );

        return view('portal.kit-groups.edit', compact('kitGroup', 'assignableItems', 'deleteConfirmation'));
    }

    public function update(UpdatePortalKitGroupRequest $request, KitGroup $kitGroup): RedirectResponse
    {
        $client = auth()->user()->client;
        $data = $request->validated();
        $newIds = collect($data['kit_item_ids'] ?? [])->map(fn ($v) => (int) $v)->all();

        [$attached, $detached] = DB::transaction(function () use ($kitGroup, $client, $data, $newIds) {
            $kitGroup->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $currentIds = $kitGroup->kitItems()->pluck('id')->all();

            $detached = array_values(array_diff($currentIds, $newIds));
            $attached = array_values(array_diff($newIds, $currentIds));

            if (! empty($detached)) {
                KitItem::whereIn('id', $detached)
                    ->where('client_id', $client->id)
                    ->where('kit_group_id', $kitGroup->id)
                    ->update(['kit_group_id' => null]);
            }

            if (! empty($attached)) {
                KitItem::whereIn('id', $attached)
                    ->where('client_id', $client->id)
                    ->update(['kit_group_id' => $kitGroup->id]);
            }

            return [$attached, $detached];
        });

        AuditLog::record(
            'updated',
            'KitGroup',
            $kitGroup->id,
            "Client updated kit group {$kitGroup->name}",
            [
                'kit_item_ids_attached' => $attached,
                'kit_item_ids_detached' => $detached,
            ]
        );

        return redirect()->route('portal.kit-groups.show', $kitGroup)
            ->with('success', 'Kit group updated.');
    }

    public function destroy(Request $request, KitGroup $kitGroup): RedirectResponse
    {
        $this->authorize('manage-own-kit-group', $kitGroup);

        $confirmation = $this->makeConfirmedAction(
            'delete.kit-group',
            'KitGroup',
            $kitGroup->id,
            "DELETE-GROUP-{$kitGroup->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        $detachedIds = $kitGroup->kitItems()->pluck('id')->all();

        DB::transaction(function () use ($kitGroup) {
            $kitGroup->kitItems()->update(['kit_group_id' => null]);
            $kitGroup->delete();
        });

        AuditLog::record(
            'deleted',
            'KitGroup',
            $kitGroup->id,
            "Client deleted kit group {$kitGroup->name}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
                'kit_item_ids_detached' => $detachedIds,
            ]
        );

        return redirect()->route('portal.kit-groups.index')
            ->with('success', 'Kit group deleted. Items have been moved out of the group.');
    }
}
