@props(['title', 'description', 'preset', 'values'])

<div class="rounded-lg border border-gray-200 bg-white p-4 mb-6 space-y-4">
    <div>
        <h3 class="text-base font-semibold">{{ $title }}</h3>
        <p class="text-sm text-gray-600">{{ $description }}</p>
    </div>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="hidden" name="media_{{ $preset }}_do_not_resize" value="0">
        <input
            type="checkbox"
            name="media_{{ $preset }}_do_not_resize"
            value="1"
            class="mt-1 rounded border-gray-300"
            @checked((bool) old("media_{$preset}_do_not_resize", ! $values['resize']))
        >
        <span>
            <span class="block text-sm font-medium text-gray-900">Do not resize images</span>
            <span class="block text-sm text-gray-600">Store uploads at original dimensions (no variants).</span>
        </span>
    </label>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium mb-1" for="media_{{ $preset }}_max_width">Max width (px)</label>
            <input
                id="media_{{ $preset }}_max_width"
                type="number"
                name="media_{{ $preset }}_max_width"
                value="{{ old("media_{$preset}_max_width", $values['max_width']) }}"
                min="100"
                max="8000"
                class="w-full rounded-md border-gray-300"
                required
            >
            @error("media_{$preset}_max_width")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="media_{{ $preset }}_max_height">Max height (px)</label>
            <input
                id="media_{{ $preset }}_max_height"
                type="number"
                name="media_{{ $preset }}_max_height"
                value="{{ old("media_{$preset}_max_height", $values['max_height']) }}"
                min="100"
                max="8000"
                class="w-full rounded-md border-gray-300"
                required
            >
            <p class="mt-1 text-xs text-gray-500">Larger images are scaled down to fit inside this box. Default 1500×1500.</p>
            @error("media_{$preset}_max_height")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium mb-1" for="media_{{ $preset }}_width_sm">Small width (px)</label>
            <input
                id="media_{{ $preset }}_width_sm"
                type="number"
                name="media_{{ $preset }}_width_sm"
                value="{{ old("media_{$preset}_width_sm", $values['sm']) }}"
                min="100"
                max="4000"
                class="w-full rounded-md border-gray-300"
                required
            >
            @error("media_{$preset}_width_sm")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="media_{{ $preset }}_width_md">Medium width (px)</label>
            <input
                id="media_{{ $preset }}_width_md"
                type="number"
                name="media_{{ $preset }}_width_md"
                value="{{ old("media_{$preset}_width_md", $values['md']) }}"
                min="100"
                max="4000"
                class="w-full rounded-md border-gray-300"
                required
            >
            @error("media_{$preset}_width_md")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="media_{{ $preset }}_width_lg">Large width (px)</label>
            <input
                id="media_{{ $preset }}_width_lg"
                type="number"
                name="media_{{ $preset }}_width_lg"
                value="{{ old("media_{$preset}_width_lg", $values['lg']) }}"
                min="100"
                max="4000"
                class="w-full rounded-md border-gray-300"
                required
            >
            @error("media_{$preset}_width_lg")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1" for="media_{{ $preset }}_jpeg_quality">JPEG quality (%)</label>
            <input
                id="media_{{ $preset }}_jpeg_quality"
                type="number"
                name="media_{{ $preset }}_jpeg_quality"
                value="{{ old("media_{$preset}_jpeg_quality", $values['jpeg_quality']) }}"
                min="1"
                max="100"
                class="w-full rounded-md border-gray-300"
                required
            >
            <p class="mt-1 text-xs text-gray-500">Higher = better quality, larger files. Typical range: 70–90.</p>
            @error("media_{$preset}_jpeg_quality")<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
