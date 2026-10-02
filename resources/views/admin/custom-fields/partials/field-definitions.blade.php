@php
    $rows = old("custom_field_definitions.{$context}", $definitions);
    if (! is_array($rows) || $rows === []) {
        $rows = [['key' => '', 'label' => '', 'type' => 'text', 'help' => '', 'required' => false]];
    }
@endphp

<div class="mb-8 space-y-4" x-data="{ rows: @js($rows) }">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ $title }}</h3>
            @if (! empty($description))
                <p class="text-sm text-gray-600">{{ $description }}</p>
            @endif
        </div>
        <button
            type="button"
            class="text-sm text-indigo-600 hover:underline shrink-0"
            @click="rows.push({ key: '', label: '', type: 'text', help: '', required: false })"
        >
            Add field
        </button>
    </div>

    <template x-for="(row, index) in rows" :key="index">
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500" x-text="'Field ' + (index + 1)"></p>
                <button type="button" class="text-xs text-red-600 hover:underline" @click="rows.splice(index, 1)">Remove</button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-1">Key</label>
                    <input
                        type="text"
                        class="w-full rounded-md border-gray-300 font-mono text-sm"
                        x-model="row.key"
                        :name="`custom_field_definitions[{{ $context }}][${index}][key]`"
                        placeholder="pronouns"
                        pattern="[a-z][a-z0-9_]*"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Label</label>
                    <input
                        type="text"
                        class="w-full rounded-md border-gray-300"
                        x-model="row.label"
                        :name="`custom_field_definitions[{{ $context }}][${index}][label]`"
                        placeholder="Pronouns"
                    >
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select
                        class="w-full rounded-md border-gray-300"
                        x-model="row.type"
                        :name="`custom_field_definitions[{{ $context }}][${index}][type]`"
                    >
                        @foreach (\App\Support\CustomFieldSchema::typeLabels() as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 pb-2">
                        <input type="hidden" :name="`custom_field_definitions[{{ $context }}][${index}][required]`" value="0">
                        <input
                            type="checkbox"
                            class="rounded border-gray-300"
                            x-model="row.required"
                            :name="`custom_field_definitions[{{ $context }}][${index}][required]`"
                            value="1"
                        >
                        <span class="text-sm text-gray-700">Required</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Help text</label>
                <input
                    type="text"
                    class="w-full rounded-md border-gray-300"
                    x-model="row.help"
                    :name="`custom_field_definitions[{{ $context }}][${index}][help]`"
                    placeholder="Optional hint shown below the field"
                >
            </div>
        </div>
    </template>
</div>
