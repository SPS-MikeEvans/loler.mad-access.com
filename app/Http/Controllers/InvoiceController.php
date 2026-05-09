<?php

namespace App\Http\Controllers;

use App\Actions\DeleteInvoice;
use App\Http\Requests\StoreInvoiceRequest;
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
    public function __construct()
    {
        // Laravel's default password confirmation window is 3 hours.
        // We can tighten this later with custom middleware if needed.
        $this->middleware(['password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

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

    public function store(StoreInvoiceRequest $request, Client $client): RedirectResponse
    {
        $data = $request->validated();

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

        $eligibleIds = $inspections->pluck('id')->all();
        $waivedIds = collect($data['waived_inspection_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => in_array($id, $eligibleIds, true))
            ->values()
            ->all();

        $subtotal = (float) $inspections
            ->reject(fn ($i) => in_array((int) $i->id, $waivedIds, true))
            ->sum('cost');

        $discountPercent = null;
        $totalAmount = $subtotal;

        if (filled($data['fixed_total'] ?? null)) {
            $fixedTotal = (float) $data['fixed_total'];

            if ($subtotal <= 0 || $fixedTotal >= $subtotal) {
                return back()
                    ->withErrors(['fixed_total' => 'Fixed total must be less than the subtotal of the un-waived inspections.'])
                    ->withInput();
            }

            $discountPercent = round((($subtotal - $fixedTotal) / $subtotal) * 100, 2);
            $totalAmount = round($fixedTotal, 2);
        } elseif (filled($data['discount_percent'] ?? null)) {
            $discountPercent = (float) $data['discount_percent'];
            $totalAmount = round($subtotal * (1 - $discountPercent / 100), 2);
        }

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_number' => Invoice::generateNumber(),
            'issued_date' => now()->toDateString(),
            'period_from' => $data['period_from'],
            'period_to' => $data['period_to'],
            'notes' => $data['notes'] ?? null,
            'subtotal' => round($subtotal, 2),
            'discount_percent' => $discountPercent,
            'total_amount' => $totalAmount,
        ]);

        foreach ($inspections as $inspection) {
            $inspection->update([
                'invoice_id' => $invoice->id,
                'invoice_waived' => in_array((int) $inspection->id, $waivedIds, true),
            ]);
        }

        $auditDetail = "Generated invoice {$invoice->invoice_number} for {$client->name}";
        if ($discountPercent !== null) {
            $auditDetail .= " with {$discountPercent}% discount";
        }
        if (count($waivedIds) > 0) {
            $auditDetail .= ' (waived: '.count($waivedIds).')';
        }
        AuditLog::record('created', 'Invoice', $invoice->id, $auditDetail);

        return redirect()->route('clients.invoices.show', [$client, $invoice])
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Client $client, Invoice $invoice): View
    {
        $invoice->load(['inspections.kitItem.kitType', 'inspections.inspector']);
        $deleteConfirmation = auth()->user()?->isAdmin()
            ? $this->issueConfirmedAction(
                'delete.invoice',
                'Invoice',
                $invoice->id,
                "DELETE-INVOICE-{$invoice->id}"
            )
            : null;

        return view('invoices.show', compact('client', 'invoice', 'deleteConfirmation'));
    }

    public function downloadPdf(Client $client, Invoice $invoice): Response
    {
        $invoice->load(['client', 'inspections.kitItem.kitType', 'inspections.inspector']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company_name' => config('company.name'),
            'company' => config('company'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function destroy(Request $request, Client $client, Invoice $invoice): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.invoice',
            'Invoice',
            $invoice->id,
            "DELETE-INVOICE-{$invoice->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        AuditLog::record(
            'deleted',
            'Invoice',
            $invoice->id,
            "Deleted invoice {$invoice->invoice_number} for {$client->name}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        app(DeleteInvoice::class)->cascade($invoice);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Invoice deleted and inspections unlinked.');
    }
}
