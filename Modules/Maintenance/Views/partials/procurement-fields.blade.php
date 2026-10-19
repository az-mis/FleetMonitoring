{{-- Fields of one purchase request; $p is the request being edited (or null when adding), $id names the form.
     After a failed save only the form that was sent gets the typed values back. --}}
@php
    $stored = fn (string $key) => $p?->{$key} instanceof \Carbon\CarbonInterface ? $p->{$key}->format('Y-m-d') : $p?->{$key};
    $value = fn (string $key) => old('_form') === $id ? old($key) : $stored($key);
@endphp
<input type="hidden" name="_form" value="{{ $id }}">
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <div class="sm:col-span-2"><label for="{{ $id }}-description" class="{{ $label }}">Description <span class="text-red-500">*</span></label><input id="{{ $id }}-description" name="description" required maxlength="255" value="{{ $value('description') }}" placeholder="e.g. Repair and replacement of parts for Toyota Hi-Ace" class="{{ $field }}"></div>
    <div>
        <label for="{{ $id }}-category" class="{{ $label }}">Category</label>
        <select id="{{ $id }}-category" name="category" class="{{ $field }}">
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(($value('category') ?? 'Vehicle') === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $id }}-status" class="{{ $label }}">Status <span class="text-red-500">*</span></label>
        <select id="{{ $id }}-status" name="status" class="{{ $field }}">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(($value('status') ?? 'PR on process') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div><label for="{{ $id }}-pr-number" class="{{ $label }}">PR number</label><input id="{{ $id }}-pr-number" name="pr_number" maxlength="40" value="{{ $value('pr_number') }}" placeholder="e.g. 2026-06-1455" class="{{ $field }}"></div>
    <div><label for="{{ $id }}-pr-date" class="{{ $label }}">PR date</label><input type="date" id="{{ $id }}-pr-date" name="pr_date" value="{{ $value('pr_date') }}" class="{{ $field }}"></div>
    <div><label for="{{ $id }}-amount" class="{{ $label }}">Amount (₱)</label><input type="number" step="0.01" min="0" id="{{ $id }}-amount" name="amount" value="{{ $value('amount') }}" class="{{ $field }}"></div>
    <div><label for="{{ $id }}-jo-po-number" class="{{ $label }}">JO / PO number</label><input id="{{ $id }}-jo-po-number" name="jo_po_number" maxlength="60" value="{{ $value('jo_po_number') }}" class="{{ $field }}"></div>
    <div><label for="{{ $id }}-jo-po-date" class="{{ $label }}">JO / PO date</label><input type="date" id="{{ $id }}-jo-po-date" name="jo_po_date" value="{{ $value('jo_po_date') }}" class="{{ $field }}"></div>
    <div><label for="{{ $id }}-coa-date" class="{{ $label }}">COA date</label><input type="date" id="{{ $id }}-coa-date" name="coa_date" value="{{ $value('coa_date') }}" class="{{ $field }}"></div>
    <div class="sm:col-span-2"><label for="{{ $id }}-remarks" class="{{ $label }}">Remarks</label><input id="{{ $id }}-remarks" name="remarks" maxlength="2000" value="{{ $value('remarks') }}" placeholder="e.g. DV-2026-0556 for signature by the chief of accounting" class="{{ $field }}"></div>
</div>
