<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Job;
use App\Models\KitItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $qrSvg = QrCode::format('svg')->size(200)->errorCorrection('H')
            ->generate(route('jobs.scan-bag', $job->bag_qr_code));

        $deleteConfirmation = auth()->user()?->isAdmin()
            ? $this->issueConfirmedAction('delete.job', 'Job', $job->id, "DELETE-JOB-{$job->id}")
            : null;

        return view('jobs.show', compact('job', 'progress', 'qrSvg', 'deleteConfirmation'));
    }

    public function edit(Job $job): View|RedirectResponse
    {
        if (! in_array($job->status, ['draft', 'open'], true)) {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'Items can only be edited while the job is in draft or open status.');
        }

        $job->load(['client', 'kitItems']);
        $clientKitItems = KitItem::where('client_id', $job->client_id)
            ->whereNotIn('status', ['retired'])
            ->with('kitType')
            ->orderBy('id')
            ->get();

        return view('jobs.edit', compact('job', 'clientKitItems'));
    }

    public function update(UpdateJobRequest $request, Job $job): RedirectResponse
    {
        if (! in_array($job->status, ['draft', 'open'], true)) {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'This job can no longer be edited.');
        }

        $data = $request->validated();

        $job->update(['notes' => $data['notes'] ?? null]);

        $this->syncKitItems($job, $data['kit_item_ids'] ?? [], $data['condition_notes'] ?? []);

        return redirect()->route('jobs.show', $job)
            ->with('success', 'Job updated.');
    }

    public function destroy(Request $request, Job $job): RedirectResponse
    {
        abort_if(! auth()->user()?->isAdmin(), 403);

        if ($job->status !== 'returned') {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'Only returned jobs may be deleted.');
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
                'condition_notes' => $conditionNotes[$index] ?? null,
            ];
        }

        $job->kitItems()->sync($syncData);
    }
}
