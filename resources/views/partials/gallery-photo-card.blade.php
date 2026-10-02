@props([
    'index',
    'imageUrl',
    'captionName',
    'caption' => '',
    'existingId' => null,
])

<li class="gallery-photo-card rounded-lg border border-gray-200 bg-white p-4 @if($existingId) gallery-photo-card--existing @endif"
    @if($existingId) data-existing-id="{{ $existingId }}" @endif
    draggable="{{ $existingId ? 'false' : 'true' }}">
    <div class="flex gap-4">
        <div class="flex flex-col items-center gap-2 shrink-0">
            <button type="button"
                    class="gallery-photo-card__drag-handle cursor-grab text-gray-400 hover:text-gray-600 p-1"
                    tabindex="-1"
                    aria-hidden="true"
                    title="{{ __('app.gallery_drag_reorder') }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                </svg>
            </button>
            <div class="relative">
                <img src="{{ $imageUrl }}" alt="" class="gallery-photo-card__thumb w-28 h-28 rounded-lg object-cover bg-gray-100">
                @if ($index === 1)
                    <span class="gallery-photo-card__cover absolute bottom-1 left-1 right-1 rounded bg-black/70 px-1.5 py-0.5 text-center text-[10px] font-medium uppercase tracking-wide text-white">
                        {{ __('app.gallery_cover_badge') }}
                    </span>
                @endif
            </div>
            <span class="gallery-photo-card__number text-xs font-medium text-gray-500">{{ __('app.gallery_photo_number', ['number' => $index]) }}</span>
        </div>

        <div class="min-w-0 flex-1 flex flex-col gap-2">
            <label for="gallery-caption-{{ $existingId ?? 'new-'.$index }}" class="text-sm font-medium text-gray-900">
                {{ __('app.photo_description') }}
            </label>
            <textarea id="gallery-caption-{{ $existingId ?? 'new-'.$index }}"
                      name="{{ $captionName }}"
                      rows="3"
                      maxlength="500"
                      placeholder="{{ __('app.photo_description_placeholder') }}"
                      class="gallery-photo-card__caption w-full rounded-md border border-gray-300 px-3 py-2 text-sm resize-y min-h-[4.5rem]">{{ $caption }}</textarea>
            <div class="flex flex-wrap items-center justify-between gap-2 mt-auto">
                <span class="gallery-photo-card__counter text-xs text-gray-500" data-max="500">0 / 500</span>
                @if ($existingId)
                    <input type="checkbox"
                           name="remove_gallery_items[]"
                           value="{{ $existingId }}"
                           class="gallery-photo-card__remove-input sr-only">
                    <button type="button" class="gallery-photo-card__remove-btn text-sm text-red-700 hover:text-red-900 hover:underline">{{ __('app.remove') }}</button>
                @else
                    <button type="button" class="gallery-photo-card__remove-btn text-sm text-red-700 hover:text-red-900 hover:underline">
                        {{ __('app.remove') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</li>
