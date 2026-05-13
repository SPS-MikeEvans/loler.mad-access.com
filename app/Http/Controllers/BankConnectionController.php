<?php

namespace App\Http\Controllers;

use App\Events\BankConnectionLinked;
use App\Http\Requests\ConnectBankRequest;
use App\Jobs\PullConnectionBankFeed;
use App\Models\AuditLog;
use App\Models\BankConnection;
use App\Models\BankTransaction;
use App\Models\Reconciliation;
use App\Services\BankFeed\BankFeedProvider;
use App\Services\BankFeed\Exceptions\BankFeedAccessExpired;
use App\Services\BankFeed\Exceptions\BankFeedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BankConnectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:admin'])->only(['index', 'connect', 'callback', 'sync']);
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(): View
    {
        $connections = BankConnection::query()
            ->orderByDesc('created_at')
            ->get();

        $deleteConfirmations = $connections->mapWithKeys(fn (BankConnection $c) => [
            $c->id => $this->issueConfirmedAction(
                'delete.bank-connection',
                'BankConnection',
                $c->id,
                "DELETE-BANK-CONNECTION-{$c->id}"
            ),
        ]);

        return view('accounting.bank-connections.index', compact('connections', 'deleteConfirmations'));
    }

    public function connect(ConnectBankRequest $request, BankFeedProvider $provider): RedirectResponse|Response
    {
        $data = $request->validated();
        $reference = (string) Str::random(40);
        $redirectUri = (string) config('banking.gocardless.redirect_uri') ?: route('accounting.bank-connections.callback');

        try {
            $req = $provider->initiateRequisition($data['institution_id'], $reference, $redirectUri);
        } catch (BankFeedException $e) {
            Log::error('GoCardless initiate failed', ['err' => $e->getMessage()]);

            return back()->with('error', 'Could not start bank connection — '.$e->getMessage());
        }

        BankConnection::create([
            'provider' => 'gocardless',
            'institution_id' => $data['institution_id'],
            'institution_name' => $data['institution_id'],
            'requisition_id' => $req['id'],
            'requisition_reference' => $reference,
            'agreement_id' => $req['agreement_id'] ?? null,
            'status' => BankConnection::STATUS_PENDING,
            'created_by_user_id' => auth()->id(),
        ]);

        AuditLog::record('initiated', 'BankConnection', 0, "Started bank connection (ref={$reference})");

        return redirect()->away($req['link']);
    }

    public function callback(Request $request, BankFeedProvider $provider): RedirectResponse
    {
        $reference = (string) $request->query('ref', $request->query('reference', ''));
        $connection = BankConnection::where('requisition_reference', $reference)->first();

        if (! $connection) {
            return redirect()->route('accounting.bank-connections.index')->with('error', 'Unknown bank connection reference.');
        }

        try {
            $result = $provider->finalize((string) $connection->requisition_id);
        } catch (BankFeedException $e) {
            Log::error('GoCardless finalize failed', ['err' => $e->getMessage(), 'connection_id' => $connection->id]);

            return redirect()->route('accounting.bank-connections.index')->with('error', 'Could not finalize bank connection.');
        }

        $connection->update([
            'account_ids' => $result['account_ids'],
            'agreement_id' => $result['agreement_id'] ?? $connection->agreement_id,
            'status' => BankConnection::STATUS_LINKED,
            'linked_at' => now(),
            'expires_at' => $result['expires_at'] ?? null,
        ]);

        AuditLog::record('linked', 'BankConnection', $connection->id, "Linked {$connection->institution_id}");
        BankConnectionLinked::dispatch($connection);

        return redirect()->route('accounting.bank-connections.index')->with('success', 'Bank connection linked.');
    }

    public function sync(BankConnection $bankConnection): RedirectResponse
    {
        if ($bankConnection->needsRelink()) {
            return back()->with('error', 'This connection is expired or revoked — please re-link.');
        }

        PullConnectionBankFeed::dispatch($bankConnection->id);

        return back()->with('success', 'Sync queued.');
    }

    public function destroy(Request $request, BankConnection $bankConnection, BankFeedProvider $provider): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.bank-connection',
            'BankConnection',
            $bankConnection->id,
            "DELETE-BANK-CONNECTION-{$bankConnection->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        DB::transaction(function () use ($bankConnection, $provider) {
            Reconciliation::query()
                ->whereIn('bank_transaction_id', $bankConnection->bankTransactions()->pluck('id'))
                ->delete();

            $bankConnection->bankTransactions()->each(fn (BankTransaction $tx) => $tx->delete());

            try {
                if ($bankConnection->requisition_id) {
                    $provider->revoke((string) $bankConnection->requisition_id);
                }
            } catch (BankFeedAccessExpired) {
                // Already expired upstream — nothing to revoke.
            } catch (BankFeedException $e) {
                Log::warning('GoCardless revoke failed', ['err' => $e->getMessage(), 'connection_id' => $bankConnection->id]);
            }

            $bankConnection->update(['status' => BankConnection::STATUS_REVOKED]);
            $bankConnection->delete();
        });

        AuditLog::record(
            'deleted',
            'BankConnection',
            $bankConnection->id,
            "Deleted bank connection {$bankConnection->institution_id}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        return redirect()->route('accounting.bank-connections.index')->with('success', 'Bank connection removed.');
    }
}
