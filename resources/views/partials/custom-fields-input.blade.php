@php
    $context = $context ?? \App\Support\CustomFieldSchema::CONTEXT_USER;
    $definitions = \App\Support\CustomFieldSchema::forContext($context);
    $values = old('custom_fields', $value ?? []);
@endphp

@if ($definitions !== [])
    <div @class(['space-y-4', $class ?? ''])>
        @if (! empty($heading))
            <h3 class="text-sm font-medium text-gray-900">{{ $heading }}</h3>
        @endif

        @foreach ($definitions as $field)
            @php
                $fieldName = 'custom_fields.'.$field['key'];
                $inputId = 'custom_field_'.$field['key'];
                $stored = $values[$field['key']] ?? null;
            @endphp

            <div>
                @if ($field['type'] === \App\Support\CustomFieldSchema::TYPE_CHECKBOX)
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="custom_fields[{{ $field['key'] }}]" value="0">
                        <input
                            id="{{ $inputId }}"
                            type="checkbox"
                            name="custom_fields[{{ $field['key'] }}]"
                            value="1"
                            class="rounded border-gray-300 text-indigo-600"
                            @checked(filter_var(old($fieldName, $stored), FILTER_VALIDATE_BOOLEAN))
                        >
                        <span class="text-sm font-medium text-gray-900">{{ $field['label'] }}</span>
                    </label>
                @else
                    <label class="block text-sm font-medium mb-1" for="{{ $inputId }}">{{ $field['label'] }}</label>
                    @if ($field['type'] === \App\Support\CustomFieldSchema::TYPE_TEXTAREA)
                        <textarea
                            id="{{ $inputId }}"
                            name="custom_fields[{{ $field['key'] }}]"
                            rows="3"
                            class="w-full rounded-md border-gray-300"
                            @if ($field['required']) required @endif
                        >{{ old($fieldName, $stored) }}</textarea>
                    @else
                        <input
                            id="{{ $inputId }}"
                            type="{{ $field['type'] === \App\Support\CustomFieldSchema::TYPE_NUMBER ? 'number' : ($field['type'] === \App\Support\CustomFieldSchema::TYPE_URL ? 'url' : 'text') }}"
                            name="custom_fields[{{ $field['key'] }}]"
                            value="{{ old($fieldName, $stored) }}"
                            class="w-full rounded-md border-gray-300"
                            @if ($field['required']) required @endif
                        >
                    @endif
                @endif

                @if ($field['help'] !== '')
                    <p class="mt-1 text-xs text-gray-500">{{ $field['help'] }}</p>
                @endif

                @error($fieldName)
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
@endif
