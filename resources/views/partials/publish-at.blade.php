<div class="mb-6" id="publish-at-field">
    <label class="block text-sm font-medium mb-1">Publish at</label>
    <input
        type="datetime-local"
        name="publish_at"
        value="{{ old('publish_at', $publishAt ?? '') }}"
        class="w-full rounded-md border border-gray-300 px-3 py-2"
    >
    <p class="mt-1 text-xs text-gray-500">Optional. Leave empty to publish immediately. Future times stay hidden from feeds until then.</p>
    @error('publish_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>

@push('scripts')
    <script>
        (function () {
            const status = document.querySelector('[name="status"]');
            const field = document.getElementById('publish-at-field');
            if (!status || !field) return;

            const toggle = () => {
                field.style.display = status.value === 'published' ? '' : 'none';
            };

            status.addEventListener('change', toggle);
            toggle();
        })();
    </script>
@endpush
