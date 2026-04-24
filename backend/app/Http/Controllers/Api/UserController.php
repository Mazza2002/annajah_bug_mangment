<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->has('role')) {
            $query->whereHas('role', function($q) use ($request) {
                $q->where('slug', $request->role);
            });
        }

        return $query->get();
    }

    public function show(User $user)
    {
        return $user->load(['role', 'bugsAssigned', 'bugsReported']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
        ]);

        return response()->json($user->load('role'), 201);
    }

    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Mock invitation logic: Create user with default password
        $user = User::create([
            'name' => explode('@', $validated['email'])[0],
            'email' => $validated['email'],
            'password' => \Hash::make('password'),
            'role_id' => $validated['role_id'],
        ]);

        return response()->json($user->load('role'), 201);
    }

    public function changeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update(['role_id' => $validated['role_id']]);

        return response()->json($user->load('role'));
    }

    public function destroy(User $user)
    {
        if ($user->id === \Auth::id()) {
            return response()->json(['message' => 'Cannot delete yourself'], 403);
        }

        $user->delete();
        return response()->json(null, 204);
    }
}
