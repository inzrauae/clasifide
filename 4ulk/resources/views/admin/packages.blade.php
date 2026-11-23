<x-admin-shell title="Seller packages" active="packages">
    <div class="space-y-3">
        @foreach ($packages as $p)
            <form method="POST" action="{{ route('admin.packages.update', $p) }}" class="card p-4 grid md:grid-cols-[1fr_1fr] gap-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                    <label class="col-span-2"><span class="label">Name</span><input name="name" value="{{ $p->name }}" class="input !py-2"></label>
                    <label><span class="label">Price (Rs)</span><input name="price" type="number" min="0" value="{{ $p->price }}" class="input !py-2"></label>
                    <label><span class="label">Days</span><input name="duration_days" type="number" min="1" value="{{ $p->duration_days }}" class="input !py-2"></label>
                    <label><span class="label">Live ads</span><input name="active_ads" type="number" min="1" value="{{ $p->active_ads }}" class="input !py-2"></label>
                    <label><span class="label">Stores</span><input name="stores" type="number" min="0" value="{{ $p->stores }}" class="input !py-2"></label>
                    <label><span class="label">Promo credits</span><input name="promotions" type="number" min="0" value="{{ $p->promotions }}" class="input !py-2"></label>
                    <label><span class="label">Premium ads</span><input name="premium_ads" type="number" min="0" value="{{ $p->premium_ads }}" class="input !py-2"></label>
                    <div class="col-span-2 sm:col-span-4 flex flex-wrap gap-4 pt-1">
                        <label class="flex items-center gap-2"><input type="checkbox" name="analytics" value="1" @checked($p->analytics) class="accent-brand"> Analytics</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="lead_tools" value="1" @checked($p->lead_tools) class="accent-brand"> Lead tools</label>
                        <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($p->is_active) class="accent-brand"> Shown on pricing page</label>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="flex-1"><span class="label">Feature bullets (one per line)</span><textarea name="features_text" rows="5" class="input !text-sm">{{ implode("\n", $p->features ?? []) }}</textarea></label>
                    <button class="btn-primary self-end !py-2">Save {{ $p->name }}</button>
                </div>
            </form>
        @endforeach
    </div>
    <p class="mt-4 text-sm text-muted">Promotion prices and banner rates are set in <code>config/marketplace.php</code>.</p>
</x-admin-shell>
