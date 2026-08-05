<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        $staff = User::query()
            ->whereIn('role', [UserRole::Staff->value, UserRole::Owner->value])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role', 'created_at']);

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        User::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'password']),
            'role' => UserRole::Staff,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff user created.');
    }
}
