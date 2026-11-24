@php
    $fields = old('fields', collect($category->specFields())->map(fn ($f) => [
        'name' => $f['name'], 'type' => $f['type'], 'options' => implode("\n", $f['options']),
    ])->all());
    $used = \App\Models\Listing::where('category_id', $category->id)->count();
@endphp
<x-admin-shell :title="$category->name" active="categories">
    <x-admin.category-header :category="$category" tab="edit" />

    @if ($errors->any())
        <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-4"
          x-data="{ fields: @js(array_values($fields)), add() { this.fields.push({ name: '', type: 'text', options: '' }) }, move(i, d) { const j = i + d; if (j < 0 || j >= this.fields.length) return; [this.fields[i], this.fields[j]] = [this.fields[j], this.fields[i]] } }">
        @csrf @method('PUT')

        <div class="card p-4 sm:p-5">
            <h2 class="font-extrabold">Basics</h2>
            <div class="mt-3 grid sm:grid-cols-2 gap-3">
                <label><span class="label">Name</span><input name="name" value="{{ old('name', $category->name) }}" required maxlength="60" class="input"></label>
                <label><span class="label">Short name <span class="text-faint font-normal">(menus, optional)</span></span><input name="short_name" value="{{ old('short_name', $category->short_name) }}" maxlength="30" class="input"></label>
                <label class="sm:col-span-2"><span class="label">Hint shown under the name</span><input name="hint" value="{{ old('hint', $category->hint) }}" maxlength="120" class="input"></label>
                <label>
                    <span class="label">Icon <a href="https://lucide.dev/icons/" target="_blank" rel="noopener" class="text-faint font-normal hover:underline">(Lucide name)</a></span>
                    <input name="icon" value="{{ old('icon', $category->icon) }}" required maxlength="40" class="input">
                </label>
                <label><span class="label">Order in menus</span><input name="sort" type="number" value="{{ old('sort', $category->sort) }}" required class="input"></label>
            </div>
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="accent-brand"> Visible to buyers and sellers</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="has_condition" value="1" @checked(old('has_condition', $category->has_condition)) class="accent-brand"> Ask for condition (new / used)</label>
            </div>
        </div>

        <div class="card p-4 sm:p-5">
            <h2 class="font-extrabold">Premium posting fee</h2>
            <p class="text-xs text-muted">Set a fee to make this category premium. Package premium-ad credits also cover the fee.</p>
            <div class="mt-3 grid grid-cols-2 gap-3 max-w-md">
                <label><span class="label">Fee per ad (Rs)</span><input name="posting_fee" type="number" min="0" value="{{ old('posting_fee', $category->posting_fee) }}" required class="input"></label>
                <label><span class="label">Free ads / seller / month</span><input name="free_ads_per_month" type="number" min="0" max="100" value="{{ old('free_ads_per_month', $category->free_ads_per_month) }}" required class="input"></label>
            </div>
        </div>

        <div class="card p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="font-extrabold">Spec fields</h2>
                    <p class="text-xs text-muted">What sellers fill in when they post a {{ $category->label() }} ad. Choice lists become filters on the Ads tab.</p>
                </div>
                <button type="button" @click="add()" class="btn-ghost !py-1.5 text-sm">@svg('lucide-plus', 'w-4 h-4') Add field</button>
            </div>
            @if ($used)
                <p class="mt-3 rounded-xl bg-[#FFFBEB] border border-[#FDE68A] px-3 py-2 text-xs text-[#78350F]">{{ number_format($used) }} ads already use these fields. Renaming a field hides what sellers entered under the old name.</p>
            @endif

            <ol class="mt-3 space-y-2">
                <template x-for="(f, i) in fields" :key="i">
                    <li class="rounded-xl border border-line p-3 grid grid-cols-[auto_1fr] sm:grid-cols-[auto_1fr_160px_auto] gap-2 items-start">
                        <div class="flex flex-col">
                            <button type="button" @click="move(i, -1)" :disabled="i === 0" class="p-1 text-muted hover:text-ink disabled:opacity-30" aria-label="Move up">@svg('lucide-arrow-up', 'w-4 h-4')</button>
                            <button type="button" @click="move(i, 1)" :disabled="i === fields.length - 1" class="p-1 text-muted hover:text-ink disabled:opacity-30" aria-label="Move down">@svg('lucide-arrow-down', 'w-4 h-4')</button>
                        </div>
                        <div>
                            <label class="sr-only" :for="'f-name-' + i">Field name</label>
                            <input :id="'f-name-' + i" :name="'fields[' + i + '][name]'" x-model="f.name" placeholder="Field name, e.g. Brand" required maxlength="40" class="input !py-1.5">
                            <template x-if="f.type === 'select'">
                                <div class="mt-2">
                                    <label class="text-xs text-muted" :for="'f-opt-' + i">Choices, one per line</label>
                                    <textarea :id="'f-opt-' + i" :name="'fields[' + i + '][options]'" x-model="f.options" :rows="Math.max(3, (f.options || '').split('\n').length + 1)" class="input !py-1.5 text-sm"></textarea>
                                </div>
                            </template>
                        </div>
                        <div class="col-start-2 sm:col-start-auto">
                            <label class="sr-only" :for="'f-type-' + i">Field type</label>
                            <select :id="'f-type-' + i" :name="'fields[' + i + '][type]'" x-model="f.type" class="input !py-1.5 !text-sm">
                                @foreach (\App\Models\Category::FIELD_TYPES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <button type="button" @click="fields.splice(i, 1)" class="col-start-2 sm:col-start-auto justify-self-start p-2 rounded-lg text-red-600 hover:bg-red-50" aria-label="Remove field">@svg('lucide-trash-2', 'w-4 h-4')</button>
                    </li>
                </template>
            </ol>
            <p x-show="!fields.length" class="mt-3 text-sm text-muted">No spec fields. Sellers only fill in the title, price and description.</p>
        </div>

        <div class="flex gap-2">
            <button class="btn-primary">Save {{ $category->name }}</button>
            <a href="{{ route('admin.categories.show', $category) }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-admin-shell>
