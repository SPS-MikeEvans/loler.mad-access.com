<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $invoices = Invoice::query()
            ->with('client')
            ->when($status === 'unpaid', fn ($q) => $q->unpaid())
            ->when(InvoiceStatus::tryFrom($status), fn ($q, $statusEnum) => $q->where('status', $statusEnum->value))
            ->when($request->filled('client'), fn ($q) => $q->where('client_id', $request->integer('client')))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q) => $q->withTrashed()->where('name', 'like', "%{$search}%"));
            }))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('issued_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('issued_date', '<=', $request->date('to')))
            ->orderByDesc('issued_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $clients = Client::orderBy('name')->get(['id', 'name']);

        $cancelConfirmations = [];
        foreach ($invoices as $invoice) {
            if (! $invoice->status->isTerminal() && ! $invoice->client->trashed()) {
                $cancelConfirmations[$invoice->id] = $this->issueConfirmedAction(
                    'cancel.invoice',
                    'Invoice',
                    $invoice->id,
                    "CANCEL-INVOICE-{$invoice->id}"
                );
            }
        }

        return view('accounting.invoices.index', compact('invoices', 'clients', 'cancelConfirmations'));
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if ($invoice->status->isTerminal()) {
            return redirect()->route('accounting.invoices.index')
                ->with('error', 'Paid or cancelled invoices cannot be edited.');
        }

        return view('accounting.invoices.edit', compact('invoice'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status->isTerminal()) {
            return redirect()->route('accounting.invoices.index')
                ->with('error', 'Paid or cancelled invoices cannot be edited.');
        }

        $data = $request->validated();

        $discount = filled($data['discount_percent'] ?? null) ? (float) $data['discount_percent'] : null;

        $invoice->fill([
            'issued_date' => $data['issued_date'],
            'due_date' => $data['due_date'],
            'period_from' => $data['period_from'],
            'period_to' => $data['period_to'],
            'notes' => $data['notes'] ?? null,
        ]);
        $invoice->discount_percent = $discount;
        $invoice->total_amount = $discount !== null
            ? round((float) $invoice->subtotal * (1 - $discount / 100), 2)
            : round((float) $invoice->subtotal, 2);
        $invoice->save();

        AuditLog::record(
            'updated',
            'Invoice',
            $invoice->id,
            "Updated invoice {$invoice->invoice_number}",
            ['changes' => $invoice->getChanges()]
        );

        return redirect()->route('accounting.invoices.index')
            ->with('success', "Invoice {$invoice->invoice_number} updated.");
    }

    public function pauseChases(Invoice $invoice): RedirectResponse
    {
        if ($invoice->chase_emails_paused_at) {
            return back()->with('error', "Automated emails are already paused for {$invoice->invoice_number}.");
        }

        $invoice->chase_emails_paused_at = now();
        $invoice->save();

        AuditLog::record(
            'chases_paused',
            'Invoice',
            $invoice->id,
            "Paused automated chase emails for invoice {$invoice->invoice_number}"
        );

        return back()->with('success', "Automated emails paused for {$invoice->invoice_number}.");
    }

    public function resumeChases(Invoice $invoice): RedirectResponse
    {
        if (! $invoice->chase_emails_paused_at) {
            return back()->with('error', "Automated emails are not paused for {$invoice->invoice_number}.");
        }

        $invoice->chase_emails_paused_at = null;
        $invoice->save();

        AuditLog::record(
            'chases_resumed',
            'Invoice',
            $invoice->id,
            "Resumed automated chase emails for invoice {$invoice->invoice_number}"
        );

        return back()->with('success', "Automated emails resumed for {$invoice->invoice_number}.");
    }
}
