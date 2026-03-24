<?php

namespace App\Http\Controllers;

use App\Actions\CreateClientPortalUser;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\AuditLog;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private readonly CreateClientPortalUser $portalAction)
    {
        // Laravel's default password confirmation window is 3 hours.
        // We can tighten this later with custom middleware if needed.
        $this->middleware(['role:admin', 'password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(): View
    {
        $clients = Client::latest('created_at')->get();
        $deleteConfirmations = auth()->user()?->isAdmin()
            ? $clients->mapWithKeys(fn (Client $client) => [
                $client->id => $this->issueConfirmedAction(
                    'delete.client',
                    'Client',
                    $client->id,
                    "DELETE-CLIENT-{$client->id}"
                ),
            ])
            : collect();

        return view('clients.index', compact('clients', 'deleteConfirmations'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());
        $this->portalAction->execute($client);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client and portal account created.');
    }

    public function show(Client $client): View
    {
        $client->load(['invoices' => fn ($q) => $q->orderByDesc('issued_date')]);
        $deleteConfirmation = auth()->user()?->isAdmin()
            ? $this->issueConfirmedAction(
                'delete.client',
                'Client',
                $client->id,
                "DELETE-CLIENT-{$client->id}"
            )
            : null;

        return view('clients.show', compact('client', 'deleteConfirmation'));
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $confirmation = $this->makeConfirmedAction(
            'delete.client',
            'Client',
            $client->id,
            "DELETE-CLIENT-{$client->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        AuditLog::record(
            'deleted',
            'Client',
            $client->id,
            "Deleted client {$client->name}",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', "Client {$client->name} deleted.");
    }
}
