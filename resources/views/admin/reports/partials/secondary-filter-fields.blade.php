@foreach($fields as $field)
    <label class="block space-y-1">
        <span class="text-[11px] font-semibold rpt-sub">{{ $field['label'] }}</span>
        <select name="{{ $field['name'] }}"
                class="rpt-input w-full border rounded-lg px-3 py-2 text-xs font-semibold transition-shadow">
            <option value="">{{ $field['placeholder'] }}</option>
            @foreach($field['options'] as $option)
                <option value="{{ $option['value'] }}" @selected((string) ($field['value'] ?? '') === (string) $option['value'])>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
    </label>
@endforeach
