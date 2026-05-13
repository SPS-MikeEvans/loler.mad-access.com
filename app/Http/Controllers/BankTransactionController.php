<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin']);
    }

    public function index(Request $request): View
    {
        $transactions = BankTransaction::query()
            ->with('bankConnection')
            ->when($request->boolean('unreconciled'), fn ($q) => $q->whereNull('reconciled_at'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('booking_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('booking_date', '<=', $request->date('to')))
            ->orderByDesc('booking_date')
            ->paginate(50)
            ->withQueryString();

        return view('accounting.bank-transactions.index', compact('transactions'));
    }
}
