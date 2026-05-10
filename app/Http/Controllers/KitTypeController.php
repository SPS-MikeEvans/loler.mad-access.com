<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitTypeRequest;
use App\Http\Requests\UpdateKitTypeRequest;
use App\Models\AuditLog;
use App\Models\KitType;
use App\Services\KitTypeAiRefreshService;
use App\Support\DefaultChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KitTypeController extends Controller
{
    public function __construct()
    {
        // Laravel's default password confirmation window is 3 hours.
        // We can tighten this later with custom middleware if needed.
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(): View
    {
        $sortable = ['name', 'category', 'brand', 'interval_months'];
        $sort = in_array(request('sort'), $sortable) ? request('sort') : 'name';
        $dir = request('dir') === 'desc' ? 'desc' : 'asc';

        $kitTypes = KitType::orderBy($sort, $dir)->orderBy('name')->get();
        $categories = KitType::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        $deleteConfirmations = auth()->user()?->isAdmin()
            ? $kitTypes->mapWithKeys(fn (KitType $type) => [
                $type->id => $this->issueConfirmedAction(
                    'delete.kit-type',
                    'KitType',
                    $type->id,
                    "DELETE-TYPE-{$type->id}"
                ),
            ])
            : collect();

        return view('kit-types.index', compact('kitTypes', 'categories', 'sort', 'dir', 'deleteConfirmations'));
    }

    public function create(): View
    {
        $defaultChecklist = DefaultChecklist::items();

        return view('kit-types.create', compact('defaultChecklist'));
    }

    public function store(StoreKitTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['lifts_people'] = $request->boolean('lifts_people');
        $data['resources_links'] = $this->filterLinks($data['resources_links'] ?? []) ?: null;
        $data['checklist_json'] = $this->filterChecklist($data['checklist_json'] ?? []) ?: null;

        if ($request->hasFile('spec_pdf_path')) {
            $data['spec_pdf_path'] = $request->file('spec_pdf_path')->store('kit-type-pdfs', 'public');
        } else {
            unset($data['spec_pdf_path']);
        }

        if ($request->hasFile('inspection_pdf_path')) {
            $data['inspection_pdf_path'] = $request->file('inspection_pdf_path')->store('kit-type-pdfs', 'public');
        } else {
            unset($data['inspection_pdf_path']);
        }

        KitType::create($data);

        return redirect()->route('kit-types.index')
            ->with('success', 'Kit type added successfully.');
    }

    public function edit(KitType $kitType): View
    {
        $defaultChecklist = DefaultChecklist::items();

        return view('kit-types.edit', compact('kitType', 'defaultChecklist'));
    }

    public function update(UpdateKitTypeRequest $request, KitType $kitType): RedirectResponse
    {
        $data = $request->validated();
        $data['lifts_people'] = $request->boolean('lifts_people');
        $data['resources_links'] = $this->filterLinks($data['resources_links'] ?? []) ?: null;
        $data['checklist_json'] = $this->filterChecklist($data['checklist_json'] ?? []) ?: null;

        if ($request->hasFile('spec_pdf_path')) {
            if ($kitType->spec_pdf_path) {
                Storage::disk('public')->delete($kitType->spec_pdf_path);
            }
            $data['spec_pdf_path'] = $request->file('spec_pdf_path')->store('kit-type-pdfs', 'public');
        } elseif ($request->boolean('remove_spec_pdf')) {
            if ($kitType->spec_pdf_path) {
                Storage::disk('public')->delete($kitType->spec_pdf_path);
            }
            $data['spec_pdf_path'] = null;
        } else {
            unset($data['spec_pdf_path']);
        }

        if ($request->hasFile('inspection_pdf_path')) {
            if ($kitType->inspection_pdf_path) {
                Storage::disk('public')->delete($kitType->inspection_pdf_path);
            }
            $data['inspection_pdf_path'] = $request->file('inspection_pdf_path')->store('kit-type-pdfs', 'public');
        } elseif ($request->boolean('remove_inspection_pdf')) {
            if ($kitType->inspection_pdf_path) {
                Storage::disk('public')->delete($kitType->inspection_pdf_path);
            }
            $data['inspection_pdf_path'] = null;
        } else {
            unset($data['inspection_pdf_path']);
        }

        $kitType->update($data);
        $kitType->update(['ai_suggested' => false]);

        return redirect()->route('kit-types.index')
            ->with('success', 'Kit type updated successfully.');
    }

    public function refresh(KitTypeAiRefreshService $service): RedirectResponse
    {
        $totals = $service->run();

        $updated = $totals['updated'] ?? 0;
        $message = "{$totals['added']} new equipment types added, {$updated} updated, {$totals['skipped']} unchanged.";

        return redirect()->route('kit-types.index')->with('success', $message);
    }

    public function destroy(Request $request, KitType $kitType): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.kit-type',
            'KitType',
            $kitType->id,
            "DELETE-TYPE-{$kitType->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        if ($kitType->kitItems()->exists()) {
            return redirect()->route('kit-types.index')
                ->with('error', "Cannot delete \"{$kitType->name}\" — it is assigned to one or more kit items.");
        }

        if ($kitType->spec_pdf_path) {
            Storage::disk('public')->delete($kitType->spec_pdf_path);
        }

        if ($kitType->inspection_pdf_path) {
            Storage::disk('public')->delete($kitType->inspection_pdf_path);
        }

        AuditLog::record(
            'deleted',
            'KitType',
            $kitType->id,
            "Deleted kit type {$kitType->name}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        $kitType->delete();

        return redirect()->route('kit-types.index')
            ->with('success', 'Kit type deleted.');
    }

    /** @param array<int, array<string, string>>|null $rows */
    private function filterLinks(?array $rows): array
    {
        return collect($rows ?? [])
            ->filter(fn ($r) => ! empty(trim($r['name'] ?? '')) || ! empty(trim($r['url'] ?? '')))
            ->values()
            ->all();
    }

    /** @param array<int, array<string, string>>|null $rows */
    private function filterChecklist(?array $rows): array
    {
        return collect($rows ?? [])
            ->filter(fn ($r) => ! empty(trim($r['text'] ?? '')))
            ->values()
            ->all();
    }
}
