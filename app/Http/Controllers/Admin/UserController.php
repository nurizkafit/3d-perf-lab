<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeUserStatusRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Account management (PRD.md §4, SITEMAP.md §3.5). There is no self-registration. */
class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('pages.admin.users.index', [
            'users' => User::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('pages.admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + ['is_active' => true]);

        return redirect()->route('admin.users.index')
            ->with('status', "Created {$user->name}. Give them the temporary password in person or through a secure channel.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('pages.admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('admin.users.index')->with('status', "Saved {$user->name}.");
    }

    public function deactivate(ChangeUserStatusRequest $request, User $user): RedirectResponse
    {
        $user->update(['is_active' => false]);

        return back()->with('status', "Deactivated {$user->name}. Their assets, tests, and reports stay.");
    }

    public function activate(ChangeUserStatusRequest $request, User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        return back()->with('status', "Activated {$user->name}.");
    }

    public function resetPassword(ResetPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => $request->validated('password')]);

        return back()->with('status', "Set a new temporary password for {$user->name}.");
    }
}
