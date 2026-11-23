<x-admin-shell title="Categories & premium fees" active="categories">
    <p class="text-sm text-muted mb-4">Set a posting fee to make a category premium (e.g. Vehicles, Property). Sellers get the "free per month" quota first; package premium-ad credits also waive the fee.</p>
    <div class="card divide-y divide-line-soft">
        @foreach ($categories as $c)
            <form method="POST" action="{{ route('admin.categories.update', $c->id) }}" class="p-3 grid grid-cols-2 md:grid-cols-[40px_1fr_120px_120px_80px_auto_auto] gap-3 items-center text-sm">
                @csrf @method('PUT')
                <span class="hidden md:flex w-9 h-9 rounded-lg bg-lilac text-brand items-center justify-center">@svg('lucide-'.$c->icon, 'w-4 h-4')</span>
                <label class="col-span-2 md:col-span-1"><span class="sr-only">Name</span><input name="name" value="{{ $c->name }}" class="input !py-1.5"><span class="text-xs text-faint">{{ $c->listings_count }} ads</span></label>
                <label><span class="text-xs text-muted">Fee (Rs)</span><input name="posting_fee" type="number" min="0" value="{{ $c->posting_fee }}" class="input !py-1.5"></label>
                <label><span class="text-xs text-muted">Free / month</span><input name="free_ads_per_month" type="number" min="0" value="{{ $c->free_ads_per_month }}" class="input !py-1.5"></label>
                <label><span class="text-xs text-muted">Order</span><input name="sort" type="number" value="{{ $c->sort }}" class="input !py-1.5"></label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="accent-brand"> Active</label>
                <button class="btn-ghost !py-1.5">Save</button>
            </form>
        @endforeach
    </div>
</x-admin-shell>
