<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitGroupRequest;
use App\Http\Requests\UpdateKitGroupRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\KitGroup;
use App\Models\KitItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KitGroupController extends Controller
{
    public function index(Client $client, Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        $query = $client->kitGroups()->withCount('kitItems');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $kitGroups = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('kit-groups.index', compact('client', 'kitGroups', 'search'));
    }

    public function create(Client $client): View
    {
        $assignableItems = $client->kitItems()
            ->whereNull('kit_group_id')
            ->whereNotIn('status', ['retired'])
            ->with('kitType')
            ->orderBy('id')
            ->get();

        return view('kit-groups.create', compact('client', 'assignableItems'));
    }

    public function store(StoreKitGroupRequest $request, Client $client): RedirectResponse
    {
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
            "Back-office user created kit group {$group->name} for client {$client->name}",
            [
                'client_id' => $client->id,
                'kit_item_ids_attached' => array_values($itemIds),
            ]
        );

        return redirect()->route('clients.kit-groups.show', [$client, $group])
            ->with('success', 'Kit group created.');
    }

    public function show(Client $client, KitGroup $kitGroup): View
    {
        abort_unless($kitGroup->client_id === $client->id, 404);

        $kitGroup->load(['kitItems' => fn ($q) => $q->with('kitType')->orderBy('id')]);

        return view('kit-groups.show', compact('client', 'kitGroup'));
    }

    public function edit(Client $client, KitGroup $kitGroup): View
    {
        abort_unless($kitGroup->client_id === $client->id, 404);

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

        return view('kit-groups.edit', compact('client', 'kitGroup', 'assignableItems', 'deleteConfirmation'));
    }

    public function update(UpdateKitGroupRequest $request, Client $client, KitGroup $kitGroup): RedirectResponse
    {
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
            "Back-office user updated kit group {$kitGroup->name} for client {$client->name}",
            [
                'client_id' => $client->id,
                'kit_item_ids_attached' => $attached,
                'kit_item_ids_detached' => $detached,
            ]
        );

        return redirect()->route('clients.kit-groups.show', [$client, $kitGroup])
            ->with('success', 'Kit group updated.');
    }

    public function destroy(Request $request, Client $client, KitGroup $kitGroup): RedirectResponse
    {
        abort_unless($kitGroup->client_id === $client->id, 404);

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
            "Back-office user deleted kit group {$kitGroup->name} for client {$client->name}",
            [
                'client_id' => $client->id,
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
                'kit_item_ids_detached' => $detachedIds,
            ]
        );

        return redirect()->route('clients.kit-groups.index', $client)
            ->with('success', 'Kit group deleted. Items have been moved out of the group.');
    }
}
