<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortalJobRequest;
use App\Models\AuditLog;
use App\Models\Job;
use App\Models\KitItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobController extends Controller
{
    private const WIZARD_KEY = 'portal.job_wizard';

    public function index(): View
    {
        $jobs = Job::where('client_id', auth()->user()->client_id)
            ->with(['client'])
            ->latest()
            ->paginate(20);

        return view('portal.jobs.index', compact('jobs'));
    }

    public function show(Job $job): View
    {
        abort_if($job->client_id !== auth()->user()->client_id, 403);

        $job->load(['kitItems.kitType', 'inspections' => fn ($q) => $q->where('inspection_job_id', $job->id)]);

        $progress = $job->inspectionProgress();

        return view('portal.jobs.show', compact('job', 'progress'));
    }

    public function create(): View
    {
        $client = auth()->user()->client;
        $wizard = session(self::WIZARD_KEY, []);
        $selectedIds = collect($wizard['kit_item_ids'] ?? [])->map(fn ($v) => (int) $v)->all();

        $availableItems = $client
            ? $client->kitItems()
                ->with(['kitType', 'kitGroup'])
                ->whereNotIn('status', ['retired'])
                ->orderBy('kit_group_id')
                ->orderBy('id')
                ->get()
            : collect();

        $itemsByGroup = $availableItems->groupBy(fn (KitItem $item) => $item->kit_group_id ?? 'none');
        $kitGroups = $client ? $client->kitGroups()->orderBy('name')->get()->keyBy('id') : collect();

        return view('portal.jobs.create', compact('itemsByGroup', 'kitGroups', 'selectedIds'));
    }

    public function chooseDate(Request $request): View|RedirectResponse
    {
        $clientId = auth()->user()->client_id;

        $validated = $request->validate([
            'kit_item_ids' => ['required', 'array', 'min:1'],
            'kit_item_ids.*' => [
                'integer',
                Rule::exists('kit_items', 'id')->where(function ($query) use ($clientId) {
                    $query->where('client_id', $clientId)->where('status', '!=', 'retired');
                }),
            ],
        ], [
            'kit_item_ids.required' => 'Please select at least one item.',
            'kit_item_ids.min' => 'Please select at least one item.',
        ]);

        $wizard = session(self::WIZARD_KEY, []);
        $wizard['kit_item_ids'] = array_map('intval', $validated['kit_item_ids']);
        session([self::WIZARD_KEY => $wizard]);

        return view('portal.jobs.date', [
            'selectedIds' => $wizard['kit_item_ids'],
            'dropOffAt' => $wizard['drop_off_at'] ?? null,
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addWeeks(4)->toDateString(),
        ]);
    }

    public function review(Request $request): View|RedirectResponse
    {
        $wizard = session(self::WIZARD_KEY, []);

        if (empty($wizard['kit_item_ids'])) {
            return redirect()->route('portal.jobs.create')
                ->with('error', 'Please pick the items you want inspected.');
        }

        $maxDate = now()->addWeeks(4)->toDateString();
        $validated = $request->validate([
            'drop_off_at' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.$maxDate],
        ], [
            'drop_off_at.before_or_equal' => 'Drop-off date must be within the next 4 weeks.',
        ]);

        $wizard['drop_off_at'] = $validated['drop_off_at'];
        $wizard['notes'] = (string) $request->input('notes', $wizard['notes'] ?? '');
        session([self::WIZARD_KEY => $wizard]);

        $client = auth()->user()->client;
        $items = $client
            ? $client->kitItems()
                ->with(['kitType', 'kitGroup'])
                ->whereIn('id', $wizard['kit_item_ids'])
                ->get()
            : collect();

        $costPence = 0;
        $missingPriceCount = 0;
        foreach ($items as $item) {
            $price = $item->kitType?->inspection_price;
            if ($price === null) {
                $missingPriceCount++;
            } else {
                $costPence += (int) round($price * 100);
            }
        }

        return view('portal.jobs.review', [
            'items' => $items,
            'dropOffAt' => $wizard['drop_off_at'],
            'notes' => $wizard['notes'],
            'costPence' => $costPence,
            'missingPriceCount' => $missingPriceCount,
        ]);
    }

    public function store(StorePortalJobRequest $request): RedirectResponse
    {
        $client = auth()->user()->client;
        $validated = $request->validated();

        $items = KitItem::whereIn('id', $validated['kit_item_ids'])
            ->where('client_id', $client->id)
            ->with('kitType')
            ->get();

        $costPence = 0;
        foreach ($items as $item) {
            $price = $item->kitType?->inspection_price;
            if ($price !== null) {
                $costPence += (int) round($price * 100);
            }
        }

        $job = DB::transaction(function () use ($client, $validated, $items) {
            $job = Job::create([
                'client_id' => $client->id,
                'created_by_user_id' => auth()->id(),
                'status' => 'draft',
                'drop_off_at' => $validated['drop_off_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $sync = [];
            foreach ($items as $item) {
                $sync[$item->id] = ['condition_notes' => null];
            }
            $job->kitItems()->sync($sync);

            return $job;
        });

        AuditLog::record(
            'created',
            'Job',
            $job->id,
            "Client requested inspection job {$job->job_number}",
            [
                'kit_item_ids' => $validated['kit_item_ids'],
                'estimated_cost_pence' => $costPence,
            ]
        );

        session()->forget(self::WIZARD_KEY);

        return redirect()->route('portal.jobs.show', $job)
            ->with('success', "Inspection request {$job->job_number} submitted. Our team will review it shortly.");
    }
}
