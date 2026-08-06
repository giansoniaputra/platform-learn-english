<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('dashboard.users', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,member'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('dashboard.users')
            ->with('status', 'Akun "'.$validated['name'].'" berhasil dibuat.');
    }

    public function grantTokens(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'amount_usd' => ['required', 'numeric', 'min:0.01'],
        ]);

        $pricePerMillion = config('services.openai.token_price_per_million');
        $tokens = (int) round($validated['amount_usd'] * 1_000_000 / $pricePerMillion);

        $user->increment('token_limit', $tokens);

        return response()->json([
            'token_limit' => $user->token_limit,
            'tokens_used' => $user->tokens_used,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if($user->isAdmin(), 403, 'Akun admin tidak bisa dihapus lewat sini.');

        $user->delete();

        return response()->json(['ok' => true]);
    }
}
