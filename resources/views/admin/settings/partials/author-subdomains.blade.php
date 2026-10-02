<h2 class="text-lg font-semibold mb-2">Author subdomain pages</h2>
<p class="text-sm text-gray-600 mb-4">
    When enabled in Features below, each author is also available at
    <code class="text-xs bg-gray-100 px-1 rounded">{username}.{{ $authorSubdomainEffectiveHost }}</code>
    (for example <code class="text-xs bg-gray-100 px-1 rounded">jane.{{ $authorSubdomainEffectiveHost }}</code>).
    Posts open at <code class="text-xs bg-gray-100 px-1 rounded">{username}.domain/{slug}</code>.
    Magazine and author host labels normalize spaces to <code>-</code> and collapse duplicate hyphens (<code>The - Commons</code> → <code>the-commons</code>). See <code>config/subdomain.php</code> and CLI key <code>subdomain_label_separator</code>.
</p>

<div class="rounded-lg border border-gray-200 bg-white p-4 mb-8 space-y-4">
    <div>
        <label class="block text-sm font-medium mb-1" for="author_subdomain_base_host">Base host (optional)</label>
        <input
            id="author_subdomain_base_host"
            type="text"
            name="author_subdomain_base_host"
            value="{{ old('author_subdomain_base_host', $authorSubdomainStoredHost) }}"
            placeholder="{{ $authorSubdomainEffectiveHost }}"
            class="w-full rounded-md border-gray-300"
            pattern="[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?"
        >
        <p class="mt-1 text-xs text-gray-500">
            Leave empty to use the hostname from <code>APP_URL</code> automatically (like WordPress site URL) — currently
            <code>{{ $authorSubdomainEffectiveHost }}</code>.
            Override only when your public hostname differs from <code>APP_URL</code> (e.g. force <code>localhost</code> while <code>APP_URL</code> uses <code>127.0.0.1</code>).
        </p>
        @error('author_subdomain_base_host')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="hidden" name="author_subdomain_redirect" value="0">
        <input
            type="checkbox"
            name="author_subdomain_redirect"
            value="1"
            class="mt-1 rounded border-gray-300"
            @checked(old('author_subdomain_redirect', $authorSubdomainRedirect))
        >
        <span>
            <span class="block text-sm font-medium text-gray-900">Redirect /@username URLs to author subdomains</span>
            <span class="block text-sm text-gray-600">301 redirect <code>/@{username}</code> and <code>/@{username}/{slug}</code> on the main site to the author subdomain. Canonical URLs always prefer the subdomain when this feature is enabled.</span>
        </span>
    </label>

    <details class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-gray-800">
        <summary class="cursor-pointer font-medium text-gray-900">DNS &amp; web server setup</summary>
        <div class="mt-3 space-y-3 text-sm leading-relaxed">
            <p><strong>1. Wildcard DNS</strong> — point <code>*.<span class="author-subdomain-doc-host">{{ $authorSubdomainEffectiveHost }}</span></code> to the same server as your main site.</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><strong>Production:</strong> add a DNS <code>A</code> or <code>CNAME</code> record: <code>*</code> → your server (or <code>*.example.com</code> → <code>example.com</code>).</li>
                <li><strong>Local (Chrome):</strong> set base host to <code>localhost</code>. Visit <code>http://username.localhost:{{ parse_url(config('app.url'), PHP_URL_PORT) ?: '8888' }}/</code>. CLI: <code>php artisan site:setting set author_subdomain_base_host localhost</code></li>
                <li><strong>Local (Safari):</strong> <code>*.localhost</code> often fails in Safari on macOS (<em>“Can't find the server”</em>) while Chrome works — use base host <code>lvh.me</code> (<code>http://username.lvh.me:8888/</code>) or add <code>/etc/hosts</code> lines. Details: <code>doc/24-site-settings.md</code> (Browser support section).</li>
            </ul>
            <p><strong>2. Web server</strong> — accept wildcard hostnames on the same vhost as the main app.</p>
            <pre class="overflow-x-auto rounded bg-gray-900 text-gray-100 p-3 text-xs">server_name {{ $authorSubdomainEffectiveHost }} *.{{ $authorSubdomainEffectiveHost }};</pre>
            <p>Docker dev nginx in this project already includes <code>*.localhost</code> when using the bundled config.</p>
            <p><strong>3. TLS</strong> — use a wildcard certificate (<code>*.example.com</code>) or HTTP-01 on each host via your provider.</p>
            <p><strong>4. APP_URL</strong> — set this to your main site URL (e.g. <code>https://example.com</code>). The framework derives author subdomain hostnames from it unless you override base host above.</p>
            <p class="text-gray-600">When the feature is disabled, only <code>/@{username}</code> URLs work and no wildcard DNS is required.</p>
        </div>
    </details>
</div>
