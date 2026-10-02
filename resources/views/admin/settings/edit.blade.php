<x-admin-layout>
    <div class="max-w-3xl">
        <h1 class="text-3xl font-bold mb-2">Settings</h1>
        <p class="text-sm text-gray-600 mb-6">Super admin only — branding and feature toggles.</p>

        @if (session('status') === 'settings-updated')
            <p class="mb-4 text-sm text-green-700">Settings saved.</p>
        @endif

        @if (session('status') === 'invite-created' && session('invite_code'))
            <p class="mb-4 text-sm text-green-700">
                Invite created. Share this link:
                <a href="{{ route('register', ['invite' => session('invite_code')]) }}" class="underline break-all">
                    {{ route('register', ['invite' => session('invite_code')]) }}
                </a>
            </p>
        @endif

        @if (session('status') === 'invite-revoked')
            <p class="mb-4 text-sm text-green-700">Invite revoked.</p>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-semibold mb-4">Branding</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Site name</label>
                <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" class="w-full rounded-md border-gray-300" required>
                @error('site_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium mb-1">Tagline</label>
                <input type="text" name="site_tagline" value="{{ old('site_tagline', $siteTagline) }}" class="w-full rounded-md border-gray-300" placeholder="Local communities, publishers, and bloggers — with integrated AI writing assistants.">
                @error('site_tagline')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <h2 class="text-lg font-semibold mb-2">Admin URL</h2>
            <p class="text-sm text-gray-600 mb-4">
                Current admin prefix: <code class="text-sm bg-gray-100 px-1.5 py-0.5 rounded">/{{ $adminPath }}</code>
                (login at <code class="text-sm bg-gray-100 px-1.5 py-0.5 rounded">/{{ $adminPath }}/login</code>).
                Change with <code class="text-sm bg-gray-100 px-1.5 py-0.5 rounded">ADMIN_PATH</code> in <code class="text-sm">.env</code>
                or <code class="text-sm bg-gray-100 px-1.5 py-0.5 rounded">php artisan site:setting set admin_path your-prefix</code>,
                then restart PHP / clear route cache.
            </p>

            <h2 class="text-lg font-semibold mb-2">Home page</h2>
            <p class="text-sm text-gray-600 mb-4">Choose how the home page lists stories for all visitors.</p>

            <div class="mb-8">
                <label class="block text-sm font-medium mb-1" for="home_layout">Layout</label>
                <select id="home_layout" name="home_layout" class="w-full rounded-md border-gray-300">
                    @foreach ($homeLayoutOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('home_layout', $homeLayout->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Discover shows all sections. Latest is a single newest-stories list. Trending highlights week, hour, and day.</p>
                @error('home_layout')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <h2 class="text-lg font-semibold mb-2">Language</h2>
            <p class="text-sm text-gray-600 mb-4">Enable one or more languages. The default is used for new visitors; the switcher appears only when more than one language is enabled.</p>

            <div class="mb-4 space-y-3">
                <p class="text-sm font-medium">Enabled languages</p>
                @foreach ($localeOptions as $code => $label)
                    <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-4 cursor-pointer hover:border-gray-300">
                        <input
                            type="checkbox"
                            name="locales_enabled[]"
                            value="{{ $code }}"
                            class="rounded border-gray-300"
                            @checked(in_array($code, old('locales_enabled', $enabledLocales), true))
                        >
                        <span class="text-sm text-gray-900">{{ $label }} ({{ strtoupper($code) }})</span>
                    </label>
                @endforeach
                @error('locales_enabled')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium mb-1" for="locale_default">Default language</label>
                <select id="locale_default" name="locale_default" class="w-full rounded-md border-gray-300">
                    @foreach ($localeOptions as $code => $label)
                        <option value="{{ $code }}" @selected(old('locale_default', $defaultLocale) === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('locale_default')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <h2 class="text-lg font-semibold mb-2">Images</h2>
            <p class="text-sm text-gray-600 mb-4">Resize and compression for new uploads. Existing images are not reprocessed.</p>

            @include('admin.settings.partials.media-fields', [
                'title' => 'Post images',
                'description' => 'Covers, editor inline images, and share images.',
                'preset' => 'post',
                'values' => $postMedia,
            ])

            @include('admin.settings.partials.media-fields', [
                'title' => 'Gallery images',
                'description' => 'Photos in gallery posts.',
                'preset' => 'gallery',
                'values' => $galleryMedia,
            ])

            @include('admin.settings.partials.author-subdomains')

            <h2 class="text-lg font-semibold mb-2">Features</h2>
            <p class="text-sm text-gray-600 mb-4">Disabled features are hidden from the site and return 404 on their routes.</p>

            <div class="space-y-4 mb-8">
                @foreach ($featureDefinitions as $key => $definition)
                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-4 cursor-pointer hover:border-gray-300">
                        <input type="hidden" name="features[{{ $key }}]" value="0">
                        <input
                            type="checkbox"
                            name="features[{{ $key }}]"
                            value="1"
                            class="mt-1 rounded border-gray-300"
                            @checked(old("features.{$key}", $features[$key] ?? false))
                        >
                        <span>
                            <span class="block text-sm font-medium text-gray-900">{{ $definition['label'] }}</span>
                            <span class="block text-sm text-gray-600">{{ $definition['description'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <h2 class="text-lg font-semibold mb-2">Comments</h2>
            <p class="text-sm text-gray-600 mb-4">Requires the Comments feature above. Use Disqus embed instead of built-in comment threads.</p>

            <div class="rounded-lg border border-gray-200 bg-white p-4 mb-8 space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="hidden" name="comments_use_disqus" value="0">
                    <input
                        type="checkbox"
                        name="comments_use_disqus"
                        value="1"
                        class="mt-1 rounded border-gray-300"
                        @checked(old('comments_use_disqus', $commentsUseDisqus))
                    >
                    <span>
                        <span class="block text-sm font-medium text-gray-900">Use Disqus</span>
                        <span class="block text-sm text-gray-600">Loads the Disqus JavaScript embed on post pages.</span>
                    </span>
                </label>

                <div>
                    <label class="block text-sm font-medium mb-1" for="disqus_shortname">Disqus shortname</label>
                    <input
                        id="disqus_shortname"
                        type="text"
                        name="disqus_shortname"
                        value="{{ old('disqus_shortname', $disqusShortname) }}"
                        placeholder="your-forum"
                        class="w-full rounded-md border-gray-300"
                        pattern="[a-zA-Z0-9_-]+"
                    >
                    <p class="mt-1 text-xs text-gray-500">From your Disqus admin URL: <code class="text-gray-700">https://<strong>shortname</strong>.disqus.com/admin</code></p>
                    @error('disqus_shortname')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md">Save</button>
        </form>

        @if ($features['registration_invites'] ?? false)
            <div class="mt-10 pt-8 border-t border-gray-200">
                <h2 class="text-lg font-semibold mb-2">Registration invites</h2>
                @if ($closedRegistration)
                    <p class="text-sm text-gray-600 mb-4">Open registration is off — new users need a valid invite code or link.</p>
                @else
                    <p class="text-sm text-gray-600 mb-4">Create invite links for closed sign-up. Turn off open registration above when you are ready.</p>
                @endif

                <form method="POST" action="{{ route('admin.settings.invites.store') }}" class="rounded-lg border border-gray-200 bg-white p-4 mb-6 space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1" for="max_uses">Max uses</label>
                            <input id="max_uses" type="number" name="max_uses" value="{{ old('max_uses', 1) }}" min="1" max="1000" class="w-full rounded-md border-gray-300">
                            @error('max_uses')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="expires_in_days">Expires in (days)</label>
                            <input id="expires_in_days" type="number" name="expires_in_days" value="{{ old('expires_in_days') }}" min="1" max="365" placeholder="Never" class="w-full rounded-md border-gray-300">
                            @error('expires_in_days')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-md text-sm">Create invite</button>
                </form>

                @if ($registrationInvites->isNotEmpty())
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 text-left text-gray-600">
                                <tr>
                                    <th class="px-4 py-2 font-medium">Code</th>
                                    <th class="px-4 py-2 font-medium">Uses</th>
                                    <th class="px-4 py-2 font-medium">Expires</th>
                                    <th class="px-4 py-2 font-medium">Status</th>
                                    <th class="px-4 py-2 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($registrationInvites as $invite)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-xs">{{ $invite->code }}</td>
                                        <td class="px-4 py-2">{{ $invite->uses_count }}@if ($invite->max_uses !== null) / {{ $invite->max_uses }}@endif</td>
                                        <td class="px-4 py-2">{{ $invite->expires_at?->format('Y-m-d') ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            @if ($invite->isValid())
                                                <span class="text-green-700">Active</span>
                                            @elseif ($invite->revoked_at)
                                                <span class="text-gray-500">Revoked</span>
                                            @else
                                                <span class="text-gray-500">Expired</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right space-x-3">
                                            @if ($invite->isValid())
                                                <a href="{{ route('register', ['invite' => $invite->code]) }}" class="text-gray-700 underline">Link</a>
                                                <form method="POST" action="{{ route('admin.settings.invites.destroy', $invite) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:underline">Revoke</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-admin-layout>
