@if (\App\Support\AdminPath::usesDefault())
    <div
        role="alert"
        class="max-w-7xl mx-auto px-4 pt-4"
        aria-live="polite"
    >
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 shadow-sm">
            <p class="leading-relaxed">{{ __('app.admin_default_path_insecure') }}</p>
        </div>
    </div>
@endif
