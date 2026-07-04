<?php

namespace App\Http\Controllers;

use App\Actions\CreateKitItemBlock;
use App\Http\Requests\StoreKitItemRequest;
use App\Http\Requests\UpdateKitItemRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Job;
use App\Models\KitItem;
use App\Models\KitType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class KitItemController extends Controller
{
    public function __construct()
    {
        // Laravel's default password confirmation window is 3 hours.
        // We can tighten this later with custom middleware if needed.
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function forClient(Client $client): JsonResponse
    {
        $items = $client->kitItems()
            ->whereNotIn('status', ['retired'])
            ->with('kitType')
            ->orderBy('id')
            ->get()
            ->map(fn (KitItem $item) => [
                'id' => $item->id,
                'label' => $item->typeName().($item->asset_tag ? ' — '.$item->asset_tag : ($item->serial_no ? ' — '.$item->serial_no : '')),
            ]);

        return response()->json($items);
    }

    public function index(Client $client, Request $request): View
    {
        $groupFilter = $request->string('group')->trim()->toString();
        $validStatuses = ['in_service', 'inspection_due', 'quarantined', 'retired', 'client_pending'];
        $statusFilter = $request->string('status')->trim()->toString();

        if (! in_array($statusFilter, $validStatuses, true)) {
            $statusFilter = '';
        }

        $query = $client->kitItems()->with(['kitType', 'kitGroup']);

        if ($groupFilter === 'none') {
            $query->whereNull('kit_group_id');
        } elseif ($groupFilter !== '' && ctype_digit($groupFilter)) {
            $query->where('kit_group_id', (int) $groupFilter);
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $kitItems = $query->orderBy('next_inspection_due')->get();
        $kitGroups = $client->kitGroups()->orderBy('name')->get();
        $deleteConfirmations = auth()->user()?->isAdmin()
            ? $kitItems->mapWithKeys(fn (KitItem $item) => [
                $item->id => $this->issueConfirmedAction(
                    'delete.kit-item',
                    'KitItem',
                    $item->id,
                    "DELETE-ITEM-{$item->id}"
                ),
            ])
            : collect();

        return view('kit-items.index', compact('client', 'kitItems', 'kitGroups', 'groupFilter', 'statusFilter', 'deleteConfirmations'));
    }

    public function create(Client $client): View
    {
        $kitTypes = KitType::orderBy('category')->orderBy('name')->get();

        return view('kit-items.create', compact('client', 'kitTypes'));
    }

    public function store(StoreKitItemRequest $request, Client $client, CreateKitItemBlock $createKitItemBlock): RedirectResponse
    {
        $data = $request->validated();
        $data['lifting_people'] = $request->boolean('lifting_people');

        $kitItems = $createKitItemBlock->execute($client, $data);

        $kitItems->each(fn (KitItem $kitItem) => $kitItem->update([
            'qr_code' => route('clients.kit-items.show', [$client, $kitItem]),
        ]));

        if ($returnToJob = request()->query('return_to_job')) {
            $job = Job::find((int) $returnToJob);
            if ($job && $job->client_id === $client->id && in_array($job->status, ['draft', 'open', 'in_progress'], true)) {
                $job->kitItems()->syncWithoutDetaching($kitItems->pluck('id')->all());

                $route = in_array($job->status, ['draft', 'open'], true) ? 'jobs.edit' : 'jobs.show';

                return redirect()->route($route, $job)
                    ->with('success', $kitItems->count().' kit '.($kitItems->count() === 1 ? 'item' : 'items').' added and attached to job.');
            }
        }

        return redirect()->route('clients.kit-items.index', $client)
            ->with('success', $kitItems->count().' kit '.($kitItems->count() === 1 ? 'item' : 'items').' added successfully.');
    }

    public function show(Client $client, KitItem $kitItem): View
    {
        $kitItem->load('kitType', 'inspections.inspector');

        $qrSvg = $kitItem->qr_code
            ? QrCode::format('svg')->size(200)->errorCorrection('H')->generate($kitItem->qr_code)
            : null;

        return view('kit-items.show', compact('client', 'kitItem', 'qrSvg'));
    }

    public function edit(Client $client, KitItem $kitItem): View
    {
        $kitTypes = KitType::orderBy('category')->orderBy('name')->get();

        return view('kit-items.edit', compact('client', 'kitItem', 'kitTypes'));
    }

    public function update(UpdateKitItemRequest $request, Client $client, KitItem $kitItem): RedirectResponse
    {
        $data = $request->validated();
        $data['lifting_people'] = $request->boolean('lifting_people');
        $data['kit_type_id'] = $data['kit_type_id'] ?? null;
        $data['custom_type_name'] = $data['custom_type_name'] ?? null;

        if (empty($data['next_inspection_due'])) {
            $kitType = empty($data['kit_type_id']) ? null : KitType::find($data['kit_type_id']);
            $startDate = $data['first_use_date']
                ?? $data['purchase_date']
                ?? $kitItem->first_use_date
                ?? $kitItem->purchase_date
                ?? null;

            if ($kitType && $startDate) {
                $intervalMonths = $data['lifting_people']
                    ? min(6, $kitType->interval_months)
                    : $kitType->interval_months;
                $data['next_inspection_due'] = Carbon::parse($startDate)->addMonths($intervalMonths);
            }
        }

        $kitItem->update($data);
        $kitItem->update(['qr_code' => route('clients.kit-items.show', [$client, $kitItem])]);

        return redirect()->route('clients.kit-items.show', [$client, $kitItem])
            ->with('success', 'Kit item updated successfully.');
    }

    public function destroy(Request $request, Client $client, KitItem $kitItem): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.kit-item',
            'KitItem',
            $kitItem->id,
            "DELETE-ITEM-{$kitItem->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        AuditLog::record(
            'deleted',
            'KitItem',
            $kitItem->id,
            "Deleted kit item {$kitItem->typeName()}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        $kitItem->delete();

        return redirect()->route('clients.kit-items.index', $client)
            ->with('success', 'Kit item deleted.');
    }
}
