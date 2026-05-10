<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\KitBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KitBrandController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin'])->only(['index', 'store', 'update']);
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(): View
    {
        $brands = KitBrand::orderBy('name')->get();
        $deleteConfirmations = $brands->mapWithKeys(fn (KitBrand $brand) => [
            $brand->id => $this->issueConfirmedAction(
                'delete.kit-brand',
                'KitBrand',
                $brand->id,
                "DELETE-BRAND-{$brand->id}"
            ),
        ]);

        return view('kit-brands.index', compact('brands', 'deleteConfirmations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:kit_brands,name'],
            'notes' => ['nullable', 'string'],
        ]);

        $brand = KitBrand::create([
            'name' => $data['name'],
            'is_active' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        AuditLog::record('created', 'KitBrand', $brand->id, "Added kit brand {$brand->name}");

        return redirect()->route('kit-brands.index')->with('success', "Brand {$brand->name} added.");
    }

    public function update(Request $request, KitBrand $kitBrand): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100', 'unique:kit_brands,name,'.$kitBrand->id],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $kitBrand->update(array_filter([
            'name' => $data['name'] ?? null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null));

        return redirect()->route('kit-brands.index')->with('success', "Brand {$kitBrand->name} updated.");
    }

    public function destroy(Request $request, KitBrand $kitBrand): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.kit-brand',
            'KitBrand',
            $kitBrand->id,
            "DELETE-BRAND-{$kitBrand->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        AuditLog::record(
            'deleted',
            'KitBrand',
            $kitBrand->id,
            "Deleted kit brand {$kitBrand->name}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        $kitBrand->delete();

        return redirect()->route('kit-brands.index')->with('success', 'Brand deleted.');
    }
}
