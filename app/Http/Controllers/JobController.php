<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Inspection;
use App\Models\Job;
use App\Models\KitItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class JobController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(Request $request): View
    {
        $query = Job::with(['client'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $jobs = $query->paginate(25)->withQueryString();
        $clients = Client::orderBy('name')->get();

        $deleteConfirmations = auth()->user()?->isAdmin()
            ? $jobs->getCollection()->mapWithKeys(fn (Job $job) => [
                $job->id => $this->issueConfirmedAction(
                    'delete.job',
                    'Job',
                    $job->id,
                    "DELETE-JOB-{$job->id}"
                ),
            ])
            : collect();

        return view('jobs.index', compact('jobs', 'clients', 'deleteConfirmations'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();

        return view('jobs.create', compact('clients'));
    }

    public function store(StoreJobRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $job = Job::create([
            'client_id' => $data['client_id'],
            'created_by_user_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->syncKitItems($job, $data['kit_item_ids'] ?? [], $data['condition_notes'] ?? []);

        AuditLog::record('created', 'Job', $job->id, "Created job {$job->job_number} for client {$job->client->name}");

        return redirect()->route('jobs.show', $job)
            ->with('success', "Job {$job->job_number} created.");
    }

    public function show(Job $job): View
    {
        $job->load(['client', 'kitItems.kitType', 'kitItems.inspections' => fn ($q) => $q->where('inspection_job_id', $job->id), 'inspections', 'photos.uploadedBy', 'createdBy']);

        $progress = $job->inspectionProgress();
        $addableKitItems = collect();

        if (in_array($job->status, ['draft', 'open', 'in_progress'], true)) {
            $attachedIds = $job->kitItems->pluck('id');
            $addableKitItems = KitItem::where('client_id', $job->client_id)
                ->whereNotIn('status', ['retired'])
                ->whereNotIn('id', $attachedIds)
                ->with('kitType')
                ->orderBy('id')
                ->get();
        }

        // For "Mark done" — collect each kit item's complete inspections that
        // are not yet linked to any job, so the admin can attach a pre-job one.
        $availablePreJobInspections = collect();
        if (in_array($job->status, ['open', 'in_progress'], true)) {
            $kitItemIds = $job->kitItems->pluck('id');
            $availablePreJobInspections = Inspection::whereIn('kit_item_id', $kitItemIds)
                ->whereNull('inspection_job_id')
                ->where('status', 'complete')
                ->orderByDesc('inspection_date')
                ->get()
                ->groupBy('kit_item_id');
        }

        $qrSvg = QrCode::format('svg')->size(200)->errorCorrection('H')
            ->generate(route('jobs.scan-bag', $job->bag_qr_code));

        $deleteConfirmation = auth()->user()?->isAdmin()
            ? $this->issueConfirmedAction('delete.job', 'Job', $job->id, "DELETE-JOB-{$job->id}")
            : null;

        return view('jobs.show', compact('job', 'progress', 'qrSvg', 'deleteConfirmation', 'availablePreJobInspections', 'addableKitItems'));
    }

    public function edit(Job $job): View|RedirectResponse
    {
        if (! in_array($job->status, ['draft', 'open'], true)) {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'Items can only be edited while the job is in draft or open status.');
        }

        $job->load(['client', 'kitItems.inspections' => fn ($q) => $q->where('inspection_job_id', $job->id)]);
        $clientKitItems = KitItem::where('client_id', $job->client_id)
            ->whereNotIn('status', ['retired'])
            ->with('kitType')
            ->orderBy('id')
            ->get();

        $lockedKitItemIds = $job->kitItems
            ->filter(fn (KitItem $item) => $item->inspections->isNotEmpty())
            ->pluck('id')
            ->all();

        return view('jobs.edit', compact('job', 'clientKitItems', 'lockedKitItemIds'));
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        if (! in_array($job->status, ['draft', 'open'], true)) {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'This job can no longer be edited.');
        }

        $data = $request->validated();
        $kitItemIds = collect($data['kit_item_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        $lockedItemIds = Inspection::where('inspection_job_id', $job->id)
            ->pluck('kit_item_id')
            ->unique()
            ->values()
            ->all();

        $removedLockedIds = array_values(array_diff($lockedItemIds, $kitItemIds));
        if (! empty($removedLockedIds)) {
            return back()
                ->withInput()
                ->with('error', 'Items with inspections linked to this job cannot be removed.');
        }

        $job->update(['notes' => $data['notes'] ?? null]);

        $this->syncKitItems($job, $kitItemIds, $data['condition_notes'] ?? []);

        return redirect()->route('jobs.show', $job)
            ->with('success', 'Job updated.');
    }

    public function addKitItems(Request $request, Job $job): RedirectResponse
    {
        if (! in_array($job->status, ['draft', 'open', 'in_progress'], true)) {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'Kit can only be added while the job is Draft, Open or In Progress.');
        }

        $data = $request->validate([
            'kit_item_ids' => ['required', 'array', 'min:1'],
            'kit_item_ids.*' => [
                'integer',
                Rule::exists('kit_items', 'id')
                    ->where('client_id', $job->client_id)
                    ->where('status', '!=', 'retired'),
            ],
        ]);

        $itemIds = collect($data['kit_item_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $attachData = $itemIds->mapWithKeys(fn (int $id) => [
            $id => ['condition_notes' => null],
        ])->all();

        $job->kitItems()->syncWithoutDetaching($attachData);

        AuditLog::record(
            'updated',
            'Job',
            $job->id,
            "Added kit items to job {$job->job_number}",
            ['kit_item_ids_added' => $itemIds->all()]
        );

        return redirect()->route('jobs.show', $job)
            ->with('success', $itemIds->count().' kit '.($itemIds->count() === 1 ? 'item' : 'items').' added to the job.');
    }

    public function markItemDone(Request $request, Job $job, KitItem $kitItem): RedirectResponse
    {
        if (! in_array($job->status, ['open', 'in_progress'], true)) {
            return back()->with('error', 'Items can only be marked done while the job is Open or In Progress.');
        }

        if ($job->client_id !== $kitItem->client_id) {
            abort(403);
        }

        if (! $job->kitItems()->where('kit_item_id', $kitItem->id)->exists()) {
            abort(404);
        }

        if (Inspection::where('kit_item_id', $kitItem->id)->where('inspection_job_id', $job->id)->exists()) {
            return back()->with('error', "{$kitItem->typeName()} is already linked to an inspection on this job.");
        }

        $validated = $request->validate([
            'inspection_id' => ['nullable', 'integer'],
        ]);

        $query = Inspection::where('kit_item_id', $kitItem->id)
            ->whereNull('inspection_job_id')
            ->where('status', 'complete');

        $inspection = ! empty($validated['inspection_id'])
            ? $query->where('id', $validated['inspection_id'])->first()
            : $query->latest('inspection_date')->first();

        if (! $inspection) {
            return back()->with(
                'error',
                "No completed pre-job inspection found for {$kitItem->typeName()}. Record an inspection first, then try again."
            );
        }

        DB::transaction(function () use ($job, $kitItem, $inspection) {
            $inspection->update(['inspection_job_id' => $job->id]);

            // Manual auto-transition. The Inspection observer only fires the
            // job transition when status is dirtied, so we replicate it here
            // for the link-only update.
            if ($job->status === 'open' && $job->canTransitionTo('in_progress')) {
                $job->update(['status' => 'in_progress']);
            }

            $progress = $job->fresh()->inspectionProgress();
            if ($progress['total'] > 0
                && $progress['done'] >= $progress['total']
                && $job->fresh()->canTransitionTo('complete')
            ) {
                $job->fresh()->update(['status' => 'complete']);
            }

            AuditLog::record(
                'updated',
                'Inspection',
                $inspection->id,
                "Linked pre-job inspection to job {$job->job_number} ({$kitItem->typeName()})",
                [
                    'job_id' => $job->id,
                    'kit_item_id' => $kitItem->id,
                    'inspection_date' => $inspection->inspection_date?->toDateString(),
                ]
            );
        });

        return redirect()->route('jobs.show', $job)
            ->with('success', "{$kitItem->typeName()} marked as done using inspection from {$inspection->inspection_date?->format('d M Y')}.");
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        abort_if(! auth()->user()?->isAdmin(), 403);

        $hasEvidence = $this->jobHasOperationalEvidence($job);

        if ($job->status !== 'returned' && $hasEvidence) {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'This job has inspections, photos or signatures and cannot be deleted until items are returned.');
        }

        if ($job->inspections()->whereNotIn('status', ['complete'])->exists()) {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'This job has open inspections and cannot be deleted.');
        }

        $confirmation = $this->makeConfirmedAction('delete.job', 'Job', $job->id, "DELETE-JOB-{$job->id}");

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        AuditLog::record(
            'deleted',
            'Job',
            $job->id,
            "Deleted job {$job->job_number}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        $job->delete();

        return redirect()->route('jobs.index')
            ->with('success', "Job {$job->job_number} deleted.");
    }

    public function bagLabel(Job $job): View
    {
        $qrSvg = QrCode::format('svg')->size(300)->errorCorrection('H')
            ->generate(route('jobs.scan-bag', $job->bag_qr_code));

        return view('jobs.bag-label', compact('job', 'qrSvg'));
    }

    public function scanBag(string $bagQrCode): RedirectResponse
    {
        $job = Job::where('bag_qr_code', $bagQrCode)->firstOrFail();

        $user = auth()->user();

        if ($user->isClientViewer()) {
            abort_if($job->client_id !== $user->client_id, 403);

            return redirect()->route('portal.jobs.show', $job);
        }

        return redirect()->route('jobs.show', $job);
    }

    public function viewDispatch(Job $job): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isClientViewer()) {
            abort_if($job->client_id !== $user->client_id, 403);

            return redirect()->route('portal.jobs.show', $job);
        }

        if ($user->isAdmin() || $user->isInspector()) {
            return redirect()->route('jobs.show', $job);
        }

        abort(403);
    }

    /** @param array<int> $kitItemIds @param array<int, string|null> $conditionNotes */
    private function syncKitItems(Job $job, array $kitItemIds, array $conditionNotes): void
    {
        $syncData = [];

        foreach ($kitItemIds as $index => $kitItemId) {
            $syncData[(int) $kitItemId] = [
                'condition_notes' => $conditionNotes[$kitItemId] ?? $conditionNotes[$index] ?? null,
            ];
        }

        $job->kitItems()->sync($syncData);
    }

    private function jobHasOperationalEvidence(Job $job): bool
    {
        return $job->inspections()->exists()
            || $job->photos()->exists()
            || filled($job->drop_off_signature_path)
            || filled($job->drop_off_signed_by)
            || filled($job->drop_off_at)
            || filled($job->return_signature_path)
            || filled($job->return_signed_by)
            || filled($job->return_at);
    }
}
