<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Inspection;
use App\Models\InspectionCheck;
use App\Models\InspectionCheckPhoto;
use App\Models\KitItem;
use App\Support\DefaultChecklist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MobileInspectionController extends Controller
{
    public function scanStart(string $qrCode): RedirectResponse
    {
        $kitItem = KitItem::where('qr_code', $qrCode)->firstOrFail();

        return redirect()->route('mobile.inspect.start', $kitItem);
    }

    public function start(KitItem $kitItem): View
    {
        $kitItem->load(['kitType', 'client']);

        $user = auth()->user();
        $competencyWarning = $this->competencyWarning($user);

        $draft = $kitItem->inspections()
            ->draft()
            ->where('inspector_user_id', $user->id)
            ->latest('started_at')
            ->first();

        return view('mobile.inspect.start', compact('kitItem', 'draft', 'competencyWarning'));
    }

    public function createDraft(Request $request, KitItem $kitItem): RedirectResponse
    {
        $kitItem->load('kitType');

        $user = auth()->user();

        if ($this->competencyWarning($user)) {
            return back()->withErrors(['competency' => 'You are not authorised to perform inspections.']);
        }

        $checklist = $kitItem->kitType->checklist_json;
        if (empty($checklist)) {
            $checklist = DefaultChecklist::items();
        }

        $inspection = Inspection::create([
            'kit_item_id'       => $kitItem->id,
            'inspector_user_id' => $user->id,
            'status'            => 'draft',
            'started_at'        => now(),
            'inspection_date'   => now()->toDateString(),
            'next_due_date'     => now()->addMonths($kitItem->kitType->interval_months ?? 6)->toDateString(),
            'overall_status'    => 'pass',
        ]);

        foreach ($checklist as $item) {
            InspectionCheck::create([
                'inspection_id'   => $inspection->id,
                'check_category'  => $item['category'],
                'check_text'      => $item['text'],
                'status'          => null,
                'notes'           => null,
            ]);
        }

        return redirect()->route('mobile.inspect.wizard', [$inspection, 0]);
    }

    public function wizard(Inspection $inspection, int $checkIndex): View|RedirectResponse
    {
        $this->authorizeInspection($inspection);

        $checks = $inspection->checks()->with('photos')->get();
        $total = $checks->count();

        if ($checkIndex < 0 || $checkIndex >= $total) {
            return redirect()->route('mobile.inspect.wizard', [$inspection, 0]);
        }

        $inspection->load('kitItem.kitType');
        $currentCheck = $checks[$checkIndex];

        return view('mobile.inspect.wizard', compact('inspection', 'checks', 'currentCheck', 'checkIndex', 'total'));
    }

    public function saveCheck(Request $request, Inspection $inspection): JsonResponse
    {
        $this->authorizeInspection($inspection);

        $data = $request->validate([
            'check_id' => ['required', 'integer', 'exists:inspection_checks,id'],
            'status'   => ['nullable', 'in:pass,fail,n/a'],
            'notes'    => ['nullable', 'string', 'max:2000'],
        ]);

        $check = InspectionCheck::where('id', $data['check_id'])
            ->where('inspection_id', $inspection->id)
            ->firstOrFail();

        $check->update([
            'status' => $data['status'] ?? null,
            'notes'  => $data['notes'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function uploadPhoto(Request $request, Inspection $inspection, InspectionCheck $check): JsonResponse
    {
        $this->authorizeInspection($inspection);

        abort_if($check->inspection_id !== $inspection->id, 403);

        $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $file = $request->file('photo');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $path = 'inspection-photos/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        $photo = InspectionCheckPhoto::create([
            'inspection_check_id' => $check->id,
            'path'                => $path,
        ]);

        return response()->json([
            'id'  => $photo->id,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function deletePhoto(Request $request, Inspection $inspection, InspectionCheckPhoto $photo): JsonResponse
    {
        $this->authorizeInspection($inspection);

        abort_if($photo->inspectionCheck->inspection_id !== $inspection->id, 403);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return response()->json(['ok' => true]);
    }

    public function completeScreen(Inspection $inspection): View
    {
        $this->authorizeInspection($inspection);
        $inspection->load(['kitItem.kitType', 'checks']);

        $passCount = $inspection->checks->where('status', 'pass')->count();
        $failCount = $inspection->checks->where('status', 'fail')->count();
        $naCount   = $inspection->checks->where('status', 'n/a')->count();
        $unanswered = $inspection->checks->whereNull('status')->count();

        return view('mobile.inspect.complete', compact('inspection', 'passCount', 'failCount', 'naCount', 'unanswered'));
    }

    public function complete(Request $request, Inspection $inspection): RedirectResponse
    {
        $this->authorizeInspection($inspection);

        $checks = $inspection->checks;

        $unanswered = $checks->whereNull('status')->count();
        if ($unanswered > 0) {
            return back()->withErrors(['checks' => "There are {$unanswered} unanswered checks. Please complete all checks before finishing."]);
        }

        $overallStatus = $checks->contains('status', 'fail') ? 'fail' : 'pass';

        $data = $request->validate([
            'report_notes'     => ['nullable', 'string', 'max:5000'],
            'digital_signature' => ['nullable', 'string'],
        ]);

        $sigPath = null;
        if (! empty($data['digital_signature'])) {
            $sigPath = 'signatures/' . Str::uuid() . '.png';
            $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $data['digital_signature']));
            Storage::disk('public')->put($sigPath, $imageData);
        }

        $inspection->update([
            'status'           => 'complete',
            'overall_status'   => $overallStatus,
            'report_notes'     => $data['report_notes'] ?? null,
            'digital_sig_path' => $sigPath,
        ]);

        $kitItem = $inspection->kitItem()->with(['client', 'kitType'])->first();

        $newKitStatus = $overallStatus === 'fail' ? 'quarantined' : 'in_service';
        $kitItem->update([
            'next_inspection_due' => $inspection->next_due_date,
            'status'              => $newKitStatus,
        ]);

        $this->generatePdf($inspection);

        $tag = $kitItem->asset_tag ?? $kitItem->serial_no ?? 'no tag';
        AuditLog::record(
            'created',
            'Inspection',
            $inspection->id,
            "Completed mobile inspection #{$inspection->id} for {$kitItem->kitType->name} ({$tag}) — result: {$overallStatus}"
        );

        return redirect()->route('mobile.inspect.done', $inspection);
    }

    public function done(Inspection $inspection): View
    {
        $this->authorizeInspection($inspection);
        $inspection->load(['kitItem.kitType', 'kitItem.client', 'checks.photos', 'inspector']);

        return view('mobile.inspect.done', compact('inspection'));
    }

    private function authorizeInspection(Inspection $inspection): void
    {
        $user = auth()->user();
        abort_if(
            ! $user->isAdmin() && $inspection->inspector_user_id !== $user->id,
            403
        );
    }

    private function competencyWarning(mixed $user): ?string
    {
        if (! $user->competent_person_flag) {
            return 'You are not marked as a competent person.';
        }

        if ($user->qualification_expiry && $user->qualification_expiry->isPast()) {
            return 'Your qualifications expired on ' . $user->qualification_expiry->format('d M Y') . '.';
        }

        return null;
    }

    private function generatePdf(Inspection $inspection): void
    {
        $inspection->load(['kitItem.kitType', 'kitItem.client', 'inspector', 'checks.photos']);

        $data = [
            'inspection'   => $inspection,
            'company_name' => config('app.name', 'LOLER Inspection Service'),
            'report_date'  => now()->format('d F Y'),
            'report_no'    => str_pad((string) $inspection->id, 6, '0', STR_PAD_LEFT),
            'verdict'      => $inspection->overall_status === 'pass'
                ? 'SAFE FOR CONTINUED USE'
                : 'NOT SAFE FOR USE – DEFECTS IDENTIFIED',
        ];

        $pdf = Pdf::loadView('pdf.inspection-report', $data);
        $pdf->setPaper('A4', 'portrait');

        $tag = $inspection->kitItem->asset_tag ?? $inspection->kitItem->serial_no ?? $inspection->id;
        $date = $inspection->inspection_date->format('Y-m-d');
        $path = "inspection-reports/loler-{$tag}-{$date}.pdf";

        Storage::disk('public')->put($path, $pdf->output());
        $inspection->update(['pdf_path' => $path]);
    }
}
