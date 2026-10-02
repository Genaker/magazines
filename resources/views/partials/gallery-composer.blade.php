<div class="mb-6" id="gallery-composer">
    <label class="block text-sm font-medium mb-2">{{ __('app.gallery_photos') }}</label>
    <p class="text-sm text-gray-500 mb-3">{{ __('app.gallery_photos_help', ['min' => config('media.min_gallery_photos'), 'max' => config('media.max_gallery_photos')]) }}</p>
    <p class="text-sm text-gray-500 mb-4">{{ __('app.gallery_captions_help') }}</p>

    @if ($errors->has('gallery_images'))
        <p class="text-sm text-red-600 mb-2">{{ $errors->first('gallery_images') }}</p>
    @endif

    <div id="gallery-dropzone"
         class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-gray-400 transition cursor-pointer">
        <p class="text-gray-600">{{ __('app.gallery_drop_photos') }}</p>
        <p class="text-sm text-gray-400 mt-1">{{ __('app.gallery_or_click') }}</p>
        <input type="file"
               id="gallery-images-input"
               name="gallery_images[]"
               accept="image/*"
               multiple
               class="sr-only">
    </div>

    <ul id="gallery-preview"
        class="gallery-photo-list space-y-3 mt-4"
        data-caption-label="{{ __('app.photo_description') }}"
        data-caption-placeholder="{{ __('app.photo_description_placeholder') }}"
        data-remove-label="{{ __('app.remove') }}"
        data-cover-label="{{ __('app.gallery_cover_badge') }}"
        data-photo-number-label="{{ __('app.gallery_photo_number', ['number' => '__NUMBER__']) }}"
        data-drag-label="{{ __('app.gallery_drag_reorder') }}">
        @if (! empty($existingItems) && $existingItems->isNotEmpty())
            @foreach ($existingItems as $item)
                @include('partials.gallery-photo-card', [
                    'index' => $loop->iteration,
                    'imageUrl' => $item->url('sm'),
                    'captionName' => 'gallery_item_captions['.$item->id.']',
                    'caption' => old('gallery_item_captions.'.$item->id, $item->caption),
                    'existingId' => $item->id,
                ])
            @endforeach
        @endif
    </ul>
</div>
