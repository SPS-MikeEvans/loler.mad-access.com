<?php

namespace App\Http\Controllers;

use App\Http\Requests\CaptureSignatureRequest;
use App\Models\AuditLog;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JobSignatureController extends Controller
{
    public function showDropOff(Job $job): View|RedirectResponse
    {
        if ($job->status !== 'draft') {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'Drop-off has already been signed for this job.');
        }

        $job->load(['client', 'kitItems.kitType']);

        return view('jobs.sign-drop-off', compact('job'));
    }

    public function captureDropOff(CaptureSignatureRequest $request, Job $job): RedirectResponse
    {
        if ($job->status !== 'draft') {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'Drop-off has already been signed for this job.');
        }

        $data = $request->validated();

        $sigPath = $this->storeSignature($data['digital_signature'], $job->id, 'drop_off');

        $job->update([
            'status' => 'open',
            'drop_off_at' => now(),
            'drop_off_signature_path' => $sigPath,
            'drop_off_signed_by' => $data['signed_by'],
            'drop_off_ip' => $request->ip(),
        ]);

        AuditLog::record(
            'updated',
            'Job',
            $job->id,
            "Drop-off signature captured for job {$job->job_number} — signed by {$data['signed_by']}"
        );

        return redirect()->route('jobs.show', $job)
            ->with('success', "Drop-off signed by {$data['signed_by']}. Print the bag label and attach it to the bag.")
            ->with('show_print_label', true);
    }

    public function showReturn(Job $job): View|RedirectResponse
    {
        if ($job->status !== 'complete') {
            return redirect()->route('jobs.show', $job)
                ->with('error', $job->status === 'returned'
                    ? 'Return has already been signed for this job.'
                    : 'This job is not yet ready for return — inspections must be completed first.');
        }

        $job->load(['client', 'kitItems.kitType']);

        return view('jobs.sign-return', compact('job'));
    }

    public function captureReturn(CaptureSignatureRequest $request, Job $job): RedirectResponse
    {
        if ($job->status !== 'complete') {
            return redirect()->route('jobs.show', $job)
                ->with('error', 'This job is not yet ready for return.');
        }

        $data = $request->validated();

        $sigPath = $this->storeSignature($data['digital_signature'], $job->id, 'return');

        $job->update([
            'status' => 'returned',
            'return_at' => now(),
            'return_signature_path' => $sigPath,
            'return_signed_by' => $data['signed_by'],
            'return_ip' => $request->ip(),
        ]);

        AuditLog::record(
            'updated',
            'Job',
            $job->id,
            "Return signature captured for job {$job->job_number} — signed by {$data['signed_by']}"
        );

        return redirect()->route('jobs.show', $job)
            ->with('success', "Items returned and signed off by {$data['signed_by']}. Job {$job->job_number} is now closed.");
    }

    private function storeSignature(string $base64Data, int $jobId, string $phase): string
    {
        $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64Data));
        $path = "signatures/jobs/{$jobId}/{$phase}_".Str::uuid().'.png';

        Storage::disk('local')->put($path, $imageData);

        return $path;
    }
}
