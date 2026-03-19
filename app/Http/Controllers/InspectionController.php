<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInspectionRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Inspection;
use App\Models\InspectionCheck;
use App\Models\KitItem;
use App\Support\DefaultChecklist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function index(Client $client, KitItem $kitItem): View
    {
        $inspections = $kitItem->inspections()
            ->with('inspector')
            ->latest('inspection_date')
            ->get();

        return view('inspections.index', compact('client', 'kitItem', 'inspections'));
    }

    public function create(Client $client, KitItem $kitItem): View
    {
        $kitItem->load('kitType');

        $checklist = $kitItem->kitType->checklist_json;
        $usingDefault = false;

        if (empty($checklist)) {
            $checklist = $this->defaultChecklist();
            $usingDefault = true;
        }

        $instructions = $kitItem->kitType->instructions;
        $links = $kitItem->kitType->resources_links ?? [];

        $uploadTokens = [];
        foreach ($checklist as $i => $check) {
            $token = Str::uuid()->toString();
            Cache::put('phone_photo:' . $token, [
                'check_index' => $i,
                'kit_item_id' => $kitItem->id,
                'user_id'     => auth()->id(),
                'check_text'  => $check['text'],
                'photos'      => [],
            ], now()->addHours(2));
            $uploadTokens[$i] = $token;
        }

        return view('inspections.create', compact('client', 'kitItem', 'checklist', 'instructions', 'links', 'usingDefault', 'uploadTokens'));
    }

    public function store(StoreInspectionRequest $request, Client $client, KitItem $kitItem): RedirectResponse
    {
        $data = $request->validated();

        $sigPath = null;
        if ($request->hasFile('digital_sig_path')) {
            $sigPath = $request->file('digital_sig_path')->store('signatures', 'public');
        }

        $inspection = Inspection::create([
            'kit_item_id'       => $kitItem->id,
            'inspector_user_id' => auth()->id(),
            'inspection_date'   => $data['inspection_date'],
            'next_due_date'     => $data['next_due_date'],
            'overall_status'    => $data['overall_status'],
            'report_notes'      => $data['report_notes'] ?? null,
            'digital_sig_path'  => $sigPath,
            'cost'              => $kitItem->kitType->inspection_price,
        ]);

        $tag = $kitItem->asset_tag ?? $kitItem->serial_no ?? 'no tag';
        AuditLog::record(
            'created',
            'Inspection',
            $inspection->id,
            "Created inspection #{$inspection->id} for {$kitItem->kitType->name} ({$tag})"
        );

        foreach ($data['checks'] as $i => $check) {
            $inspectionCheck = InspectionCheck::create([
                'inspection_id'  => $inspection->id,
                'check_category' => $check['check_category'],
                'check_text'     => $check['check_text'],
                'status'         => $check['status'],
                'notes'          => $check['notes'] ?? null,
            ]);

            if ($request->hasFile("checks.{$i}.photo")) {
                $file = $request->file("checks.{$i}.photo");
                $ext  = $file->getClientOriginalExtension() ?: 'jpg';
                $path = 'inspection-photos/' . Str::uuid() . '.' . $ext;
                Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

                \App\Models\InspectionCheckPhoto::create([
                    'inspection_check_id' => $inspectionCheck->id,
                    'path'                => $path,
                ]);
            }

            $token = $check['upload_token'] ?? null;
            if ($token) {
                $cached = Cache::get('phone_photo:' . $token);
                foreach (($cached['photos'] ?? []) as $photoPath) {
                    \App\Models\InspectionCheckPhoto::create([
                        'inspection_check_id' => $inspectionCheck->id,
                        'path'                => $photoPath,
                    ]);
                }
                Cache::forget('phone_photo:' . $token);
            }
        }

        $newStatus = match ($data['overall_status']) {
            'fail' => 'quarantined',
            'conditional' => 'inspection_due',
            default => 'in_service',
        };

        $kitItem->update([
            'next_inspection_due' => $data['next_due_date'],
            'status' => $newStatus,
        ]);

        return redirect()->route('clients.kit-items.show', [$client, $kitItem])
            ->with('success', 'Inspection saved successfully.');
    }

    public function show(Client $client, KitItem $kitItem, Inspection $inspection): View
    {
        $inspection->load('checks.photos', 'inspector');

        return view('inspections.show', compact('client', 'kitItem', 'inspection'));
    }

    public function updateCost(Request $request, Inspection $inspection): RedirectResponse
    {
        $data = $request->validate([
            'cost' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        $inspection->update(['cost' => $data['cost']]);

        AuditLog::record(
            'updated',
            'Inspection',
            $inspection->id,
            'Updated cost to £' . number_format((float) ($data['cost'] ?? 0), 2) . " on inspection #{$inspection->id}"
        );

        $kitItem = $inspection->kitItem()->with('client')->first();

        return redirect()->route('clients.kit-items.inspections.show', [$kitItem->client, $kitItem, $inspection])
            ->with('success', 'Inspection cost updated.');
    }

    public function downloadPdf(Inspection $inspection): Response
    {
        $inspection->load(['kitItem.kitType', 'kitItem.client', 'inspector', 'checks.photos']);

        $data = [
            'inspection'   => $inspection,
            'company_name' => config('app.name', 'Your LOLER Inspection Service Ltd'),
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

        return $pdf->download("loler-thorough-exam-{$tag}-{$date}.pdf");
    }

    private function defaultChecklist(): array
    {
        return DefaultChecklist::items();
    }
}
