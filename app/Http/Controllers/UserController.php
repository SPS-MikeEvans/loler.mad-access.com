<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        // Laravel's default password confirmation window is 3 hours.
        // We can tighten this later with custom middleware if needed.
        $this->middleware(['password.confirm', 'throttle:destructive-actions'])->only('destroy');
    }

    public function index(): View
    {
        $users = User::with('client')->orderBy('name')->get();
        $deleteConfirmations = $users->reject(fn (User $user) => $user->is(auth()->user()))
            ->mapWithKeys(fn (User $user) => [
                $user->id => $this->issueConfirmedAction(
                    'delete.user',
                    'User',
                    $user->id,
                    "DELETE-USER-{$user->id}"
                ),
            ]);

        return view('users.index', compact('users', 'deleteConfirmations'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();

        return view('users.create', compact('clients'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['competent_person_flag'] = $request->boolean('competent_person_flag');

        if ($data['role'] !== 'client_viewer') {
            $data['client_id'] = null;
        }

        $user = User::create($data);

        AuditLog::record('created', 'User', $user->id, "Created user {$user->name} ({$user->email}) with role {$user->role}");

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} created successfully.");
    }

    public function edit(User $user): View
    {
        $clients = Client::orderBy('name')->get();

        return view('users.edit', compact('user', 'clients'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['competent_person_flag'] = $request->boolean('competent_person_flag');

        if (filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($data['role'] !== 'client_viewer') {
            $data['client_id'] = null;
        }

        $user->update($data);

        AuditLog::record('updated', 'User', $user->id, "Updated user {$user->name} ({$user->email})");

        return redirect()->route('users.index')
            ->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $confirmation = $this->makeConfirmedAction(
            'delete.user',
            'User',
            $user->id,
            "DELETE-USER-{$user->id}"
        );

        if ($failure = $this->ensureConfirmedAction($request, $confirmation)) {
            return $failure;
        }

        $name = $user->name;
        AuditLog::record(
            'deleted',
            'User',
            $user->id,
            "Deleted user {$name} ({$user->email})",
            [
                'confirmed_action' => $confirmation->actionKey,
                'confirmation_phrase' => $confirmation->phrase,
                'confirmed_by_user_id' => auth()->id(),
                'confirmed_at' => now()->toIso8601String(),
            ]
        );
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User {$name} deleted.");
    }
}
