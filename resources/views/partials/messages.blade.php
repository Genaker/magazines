@php
    $messages = \App\Support\Message::collect(isset($errors) ? $errors : null);
    $styles = [
        'success' => 'border-green-200 bg-green-50 text-green-900',
        'error' => 'border-red-200 bg-red-50 text-red-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
        'info' => 'border-blue-200 bg-blue-50 text-blue-900',
    ];
@endphp

@if ($messages !== [])
    <div class="max-w-5xl mx-auto px-4 pt-4 space-y-3" aria-live="polite">
        @foreach ($messages as $index => $message)
            @php
                $type = $message['type'] ?? 'info';
                $class = $styles[$type] ?? $styles['info'];
            @endphp
            <div
                role="alert"
                class="site-message flex items-start gap-3 rounded-lg border px-4 py-3 text-sm shadow-sm {{ $class }}"
                data-message-index="{{ $index }}"
            >
                <p class="flex-1 leading-relaxed">{{ $message['message'] }}</p>
                <button
                    type="button"
                    class="shrink-0 rounded-md px-2 py-1 text-xs font-medium opacity-70 hover:opacity-100"
                    aria-label="{{ __('message.dismiss') }}"
                    onclick="this.closest('.site-message')?.remove()"
                >
                    {{ __('message.dismiss') }}
                </button>
            </div>
        @endforeach
    </div>
@endif
