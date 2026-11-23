<x-admin-shell title="Payments" active="orders">
    <x-admin-tabs route="admin.orders" :current="$status" :tabs="['awaiting_review' => 'Bank slips to check', 'pending' => 'Unpaid', 'paid' => 'Paid', 'failed' => 'Failed', 'all' => 'All']" />
    <form class="mb-4 flex gap-2"><input type="hidden" name="status" value="{{ $status }}"><input name="q" value="{{ request('q') }}" placeholder="Reference" class="input !py-2 max-w-xs"><button class="btn-ghost !py-2">Search</button></form>
    <div class="card overflow-x-auto">
        <table class="w-full text-sm min-w-[760px]">
            <thead class="text-left text-xs text-muted uppercase tracking-wider"><tr class="border-b border-line-soft"><th class="p-3">Reference</th><th class="p-3">Customer</th><th class="p-3">Item</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
            <tbody class="divide-y divide-line-soft">
                @forelse ($orders as $o)
                    <tr>
                        <td class="p-3 font-mono text-xs">{{ $o->reference }}<br><span class="font-sans text-faint">{{ $o->created_at->format('j M, g:i a') }}</span></td>
                        <td class="p-3">{{ $o->user?->name }}<br><span class="text-xs text-muted">{{ $o->user?->phone }}</span></td>
                        <td class="p-3"><span class="text-xs text-muted">{{ $o->typeLabel() }}</span><br>{{ $o->description }}</td>
                        <td class="p-3 font-bold whitespace-nowrap">Rs {{ number_format($o->amount) }}</td>
                        <td class="p-3 text-xs font-bold uppercase">{{ str_replace('_', ' ', $o->status) }}@if ($o->method)<br><span class="font-normal text-muted normal-case">{{ $o->method }}</span>@endif</td>
                        <td class="p-3">
                            <div class="flex gap-1.5 justify-end">
                                @if ($o->bank_slip)<a href="{{ asset($o->bank_slip) }}" target="_blank" class="btn-ghost !py-1 !px-2.5 text-xs">View slip</a>@endif
                                @unless ($o->isPaid())
                                    <form method="POST" action="{{ route('admin.orders.approve', $o) }}" onsubmit="return confirm('Mark {{ $o->reference }} as paid and deliver it?')">@csrf<button class="btn-primary !py-1 !px-2.5 text-xs">Mark paid</button></form>
                                    @if ($o->status !== 'failed')<form method="POST" action="{{ route('admin.orders.reject', $o) }}">@csrf<button class="btn-danger !py-1 !px-2.5 text-xs">Reject</button></form>@endif
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-muted">No orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-admin-shell>
