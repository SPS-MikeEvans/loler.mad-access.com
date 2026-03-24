<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\View\View;

class JobController extends Controller
{
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
}
