<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filter');

        $users = User::withCount(['listings'])->with('activeSubscription.package')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->when($filter === 'business', fn ($q) => $q->where('account_type', 'business'))
            ->when($filter === 'unverified_business', fn ($q) => $q->where('account_type', 'business')->whereNull('verified_at'))
            ->when($filter === 'verified', fn ($q) => $q->whereNotNull('verified_at'))
            ->when($filter === 'banned', fn ($q) => $q->whereNotNull('banned_at'))
            ->when($filter === 'low_trust', fn ($q) => $q->where('trust_score', '<', 30))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.users', compact('users', 'filter'));
    }

    public function verify(User $user)
    {
        $verified = ! $user->isVerified();
        $user->forceFill([
            'verified_at' => $verified ? now() : null,
            'trust_score' => $verified ? max($user->trust_score, 70) : $user->trust_score,
        ])->save();

        return back()->with('status', $verified ? "{$user->name} is now a verified seller." : "Verification removed from {$user->name}.");
    }

    public function ban(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 422, 'Admins cannot be banned.');

        if ($user->isBanned()) {
            $user->forceFill(['banned_at' => null, 'ban_reason' => null])->save();

            return back()->with('status', "{$user->name} unbanned.");
        }

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $user->forceFill(['banned_at' => now(), 'ban_reason' => $data['reason'] ?? null, 'trust_score' => 0])->save();
        $user->listings()->whereIn('status', ['active', 'pending'])->update(['status' => 'rejected', 'reject_reason' => 'Seller account suspended']);

        return back()->with('status', "{$user->name} banned and their ads taken down.");
    }

    public function role(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(['user', 'admin'])]]);
        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own role.');
        $user->forceFill(['role' => $data['role']])->save();

        return back()->with('status', 'Role updated.');
    }
}
