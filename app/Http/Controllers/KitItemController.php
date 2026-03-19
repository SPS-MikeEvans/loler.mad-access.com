<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKitItemRequest;
use App\Http\Requests\UpdateKitItemRequest;
use App\Models\Client;
use App\Models\KitItem;
use App\Models\KitType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class KitItemController extends Controller
{
    public function index(Client $client): View
    {
        $kitItems = $client->kitItems()
            ->with('kitType')
            ->orderBy('next_inspection_due')
            ->get();

        return view('kit-items.index', compact('client', 'kitItems'));
    }

    public function create(Client $client): View
    {
        $kitTypes = KitType::orderBy('category')->orderBy('name')->get();

        return view('kit-items.create', compact('client', 'kitTypes'));
    }

    public function store(StoreKitItemRequest $request, Client $client): RedirectResponse
    {
        $data = $request->validated();
        $data['client_id'] = $client->id;
        $data['lifting_people'] = $request->boolean('lifting_people');

        $kitType = KitType::find($data['kit_type_id']);
        $startDate = $data['first_use_date'] ?? $data['purchase_date'] ?? null;

        if ($startDate) {
            $intervalMonths = $data['lifting_people']
                ? min(6, $kitType->interval_months)
                : $kitType->interval_months;
            $data['next_inspection_due'] = Carbon::parse($startDate)->addMonths($intervalMonths);
        }

        $kitItem = KitItem::create($data);
        $kitItem->update(['qr_code' => route('clients.kit-items.show', [$client, $kitItem])]);

        return redirect()->route('clients.kit-items.index', $client)
            ->with('success', 'Kit item added successfully.');
    }

    public function show(Client $client, KitItem $kitItem): View
    {
        $kitItem->load('kitType', 'inspections.inspector');

        $qrSvg = $kitItem->qr_code
            ? QrCode::format('svg')->size(200)->errorCorrection('H')->generate($kitItem->qr_code)
            : null;

        return view('kit-items.show', compact('client', 'kitItem', 'qrSvg'));
    }

    public function edit(Client $client, KitItem $kitItem): View
    {
        $kitTypes = KitType::orderBy('category')->orderBy('name')->get();

        return view('kit-items.edit', compact('client', 'kitItem', 'kitTypes'));
    }

    public function update(UpdateKitItemRequest $request, Client $client, KitItem $kitItem): RedirectResponse
    {
        $data = $request->validated();
        $data['lifting_people'] = $request->boolean('lifting_people');

        if (empty($data['next_inspection_due'])) {
            $kitType = KitType::find($data['kit_type_id']);
            $startDate = $data['first_use_date']
                ?? $data['purchase_date']
                ?? $kitItem->first_use_date
                ?? $kitItem->purchase_date
                ?? null;

            if ($startDate) {
                $intervalMonths = $data['lifting_people']
                    ? min(6, $kitType->interval_months)
                    : $kitType->interval_months;
                $data['next_inspection_due'] = Carbon::parse($startDate)->addMonths($intervalMonths);
            }
        }

        $kitItem->update($data);
        $kitItem->update(['qr_code' => route('clients.kit-items.show', [$client, $kitItem])]);

        return redirect()->route('clients.kit-items.show', [$client, $kitItem])
            ->with('success', 'Kit item updated successfully.');
    }

    public function destroy(Client $client, KitItem $kitItem): RedirectResponse
    {
        $kitItem->delete();

        return redirect()->route('clients.kit-items.index', $client)
            ->with('success', 'Kit item deleted.');
    }
}
