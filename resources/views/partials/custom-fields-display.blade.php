@php
    $items = \App\Support\CustomFields::labeledValues($values ?? null, $context ?? \App\Support\CustomFieldSchema::CONTEXT_USER);
@endphp

@if ($items !== [])
    <dl class="mt-3 space-y-2 text-sm">
        @foreach ($items as $item)
            <div class="flex flex-wrap gap-x-2">
                <dt class="font-medium text-gray-700">{{ $item['label'] }}:</dt>
                <dd class="text-gray-600">
                    @if (($item['type'] ?? '') === \App\Support\CustomFieldSchema::TYPE_URL)
                        <a href="{{ $item['value'] }}" class="text-indigo-600 hover:underline" target="_blank" rel="noopener">{{ $item['value'] }}</a>
                    @else
                        {{ $item['value'] }}
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
@endif
