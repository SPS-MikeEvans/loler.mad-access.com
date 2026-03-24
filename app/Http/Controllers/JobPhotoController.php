<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobPhotoController extends Controller
{
    public function store(Request $request, Job $job): RedirectResponse
    {
        $data = $request->validate([
            'phase' => ['required', 'in:drop_off,return'],
            'photos' => ['required', 'array', 'min:1', 'max:4'],
            'photos.*' => ['required', 'image', 'max:10240'],
        ]);

        foreach ($data['photos'] as $file) {
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $path = 'job-photos/'.Str::uuid().'.'.$ext;
            Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

            JobPhoto::create([
                'inspection_job_id' => $job->id,
                'phase' => $data['phase'],
                'path' => $path,
                'uploaded_by_user_id' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Photo(s) uploaded.');
    }

    public function destroy(Job $job, JobPhoto $photo): RedirectResponse
    {
        abort_if($photo->inspection_job_id !== $job->id, 403);

        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return back()->with('success', 'Photo removed.');
    }
}
