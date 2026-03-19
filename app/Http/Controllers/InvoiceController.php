<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function create(Client $client): View
    {
        $uninvoicedInspections = $client->kitItems()
            ->with('kitType')
            ->get()
            ->flatMap(fn ($item) => $item->inspections()
                ->complete()
                ->whereNull('invoice_id')
                ->with('inspector')
                ->get()
                ->each(fn ($i) => $i->setRelation('kitItem', $item))
            )
            ->sortBy('inspection_date');

        $subtotal = $uninvoicedInspections->sum('cost');

        return view('invoices.create', compact('client', 'uninvoicedInspections', 'subtotal'));
    }

    public function store(Client $client, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_from' => ['required', 'date'],
            'period_to'   => ['required', 'date', 'after_or_equal:period_from'],
            'notes'       => ['nullable', 'string'],
        ]);

        $inspections = $client->kitItems()
            ->with('kitType')
            ->get()
            ->flatMap(fn ($item) => $item->inspections()
                ->complete()
                ->whereNull('invoice_id')
                ->whereBetween('inspection_date', [$data['period_from'], $data['period_to']])
                ->get()
            );

        if ($inspections->isEmpty()) {
            return back()->with('error', 'No uninvoiced inspections found in that date range.');
        }

        $invoice = Invoice::create([
            'client_id'      => $client->id,
            'invoice_number' => Invoice::generateNumber(),
            'issued_date'    => now()->toDateString(),
            'period_from'    => $data['period_from'],
            'period_to'      => $data['period_to'],
            'notes'          => $data['notes'] ?? null,
            'total_amount'   => $inspections->sum('cost'),
        ]);

        foreach ($inspections as $inspection) {
            $inspection->update(['invoice_id' => $invoice->id]);
        }

        AuditLog::record('created', 'Invoice', $invoice->id, "Generated invoice {$invoice->invoice_number} for {$client->name}");

        return redirect()->route('clients.invoices.show', [$client, $invoice])
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Client $client, Invoice $invoice): View
    {
        $invoice->load(['inspections.kitItem.kitType', 'inspections.inspector']);

        return view('invoices.show', compact('client', 'invoice'));
    }

    public function downloadPdf(Client $client, Invoice $invoice): Response
    {
        $invoice->load(['client', 'inspections.kitItem.kitType', 'inspections.inspector']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'      => $invoice,
            'company_name' => config('company.name'),
            'company'      => config('company'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function destroy(Client $client, Invoice $invoice): RedirectResponse
    {
        $invoice->inspections()->update(['invoice_id' => null]);
        $invoice->delete();

        AuditLog::record('deleted', 'Invoice', $invoice->id, "Deleted invoice {$invoice->invoice_number} for {$client->name}");

        return redirect()->route('clients.show', $client)
            ->with('success', 'Invoice deleted and inspections unlinked.');
    }
}
