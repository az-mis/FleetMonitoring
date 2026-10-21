{{-- Provider, division and monthly allowance, as GSS lists its cards ("Petron Fleet Card" sheet); $prefix is add|edit --}}
@php $input = 'w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none'; @endphp
<div class="grid grid-cols-2 gap-2">
    <div>
        <label for="{{ $prefix }}-card-provider" class="block text-xs font-bold text-gray-700 mb-1">Provider <span class="text-red-500">*</span></label>
        <select id="{{ $prefix }}-card-provider" name="provider" required class="{{ $input }}">
            <option value="">—</option>
            @foreach (\Modules\FleetCard\Models\FleetCard::PROVIDERS as $provider)
                <option value="{{ $provider }}">{{ $provider }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $prefix }}-card-products" class="block text-xs font-bold text-gray-700 mb-1">Allowed products</label>
        <input id="{{ $prefix }}-card-products" name="allowed_products" list="fleet-card-products" maxlength="60" placeholder="e.g. Diesel Max" class="{{ $input }}">
    </div>
</div>
<div class="grid grid-cols-2 gap-2">
    <div>
        <label for="{{ $prefix }}-card-office" class="block text-xs font-bold text-gray-700 mb-1">Division / program</label>
        <select id="{{ $prefix }}-card-office" name="office_id" class="{{ $input }}">
            <option value="">—</option>
            @foreach ($offices as $office)
                <option value="{{ $office->id }}">{{ $office->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $prefix }}-card-allowance" class="block text-xs font-bold text-gray-700 mb-1">Monthly allowance (₱)</label>
        <input type="number" step="0.01" min="0" id="{{ $prefix }}-card-allowance" name="monthly_allowance" placeholder="e.g. 20000" class="{{ $input }}">
    </div>
</div>
