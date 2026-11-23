<x-admin-shell title="Users & sellers" active="users">
    <x-admin-tabs route="admin.users" param="filter" :current="$filter ?? ''" :tabs="['' => 'All', 'business' => 'Businesses', 'unverified_business' => 'Awaiting verification', 'verified' => 'Verified', 'low_trust' => 'Low trust', 'banned' => 'Banned']" />
    <form class="mb-4 flex gap-2"><input type="hidden" name="filter" value="{{ $filter }}"><input name="q" value="{{ request('q') }}" placeholder="Name, email or phone" class="input !py-2 max-w-sm"><button class="btn-ghost !py-2">Search</button></form>
    <div class="card overflow-x-auto">
        <table class="w-full text-sm min-w-[760px]">
            <thead class="text-left text-xs text-muted uppercase tracking-wider"><tr class="border-b border-line-soft"><th class="p-3">User</th><th class="p-3">Package</th><th class="p-3">Ads</th><th class="p-3">Trust</th><th class="p-3"></th></tr></thead>
            <tbody class="divide-y divide-line-soft">
                @forelse ($users as $u)
                    <tr class="{{ $u->isBanned() ? 'bg-red-50/50' : '' }}">
                        <td class="p-3">
                            <a href="{{ route('sellers.show', [$u, 'reviews' => 1]) }}" target="_blank" class="font-bold hover:text-brand-dark">{{ $u->name }}</a>
                            @if ($u->isAdmin())<span class="badge bg-ink text-white ml-1">ADMIN</span>@endif
                            @if ($u->isVerified())<span class="badge bg-emerald-100 text-emerald-700 ml-1">VERIFIED</span>@endif
                            @if ($u->isBanned())<span class="badge bg-red-600 text-white ml-1">BANNED</span>@endif
                            <p class="text-xs text-muted">{{ $u->email }} · {{ $u->phone }} · {{ ucfirst($u->account_type) }} · joined {{ $u->created_at->format('j M Y') }}</p>
                            @if ($u->ban_reason)<p class="text-xs text-red-600">{{ $u->ban_reason }}</p>@endif
                        </td>
                        <td class="p-3">{{ $u->activeSubscription?->package->name ?? 'Free' }}</td>
                        <td class="p-3">{{ $u->listings_count }}</td>
                        <td class="p-3"><span class="font-bold {{ $u->trust_score < 30 ? 'text-red-600' : '' }}">{{ $u->trust_score }}</span></td>
                        <td class="p-3">
                            <div class="flex gap-1.5 justify-end flex-wrap">
                                <form method="POST" action="{{ route('admin.users.verify', $u) }}">@csrf<button class="btn-ghost !py-1 !px-2.5 text-xs">{{ $u->isVerified() ? 'Unverify' : 'Verify' }}</button></form>
                                @unless ($u->isAdmin())
                                    <form method="POST" action="{{ route('admin.users.ban', $u) }}" @unless($u->isBanned()) onsubmit="const r = prompt('Reason for ban?'); if (r === null) return false; this.reason.value = r;" @endunless>@csrf
                                        <input type="hidden" name="reason">
                                        <button class="{{ $u->isBanned() ? 'btn-ghost' : 'btn-danger' }} !py-1 !px-2.5 text-xs">{{ $u->isBanned() ? 'Unban' : 'Ban' }}</button>
                                    </form>
                                @endunless
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.role', $u) }}" onsubmit="return confirm('Change role for {{ $u->name }}?')">@csrf
                                        <input type="hidden" name="role" value="{{ $u->isAdmin() ? 'user' : 'admin' }}">
                                        <button class="btn-ghost !py-1 !px-2.5 text-xs">{{ $u->isAdmin() ? 'Remove admin' : 'Make admin' }}</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $users->links() }}</div>
</x-admin-shell>
