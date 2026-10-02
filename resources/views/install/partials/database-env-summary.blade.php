@if ($db['from_env'] ?? false)
    <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
        <p class="font-medium text-gray-900">Configured in <code class="text-xs bg-white px-1 rounded">.env</code></p>
        <dl class="mt-2 grid gap-1 sm:grid-cols-2">
            <div><dt class="inline text-gray-500">Type:</dt> <dd class="inline">{{ $db['connection_label'] }}</dd></div>
            <div><dt class="inline text-gray-500">Host:</dt> <dd class="inline"><code>{{ $db['host'] }}:{{ $db['port'] }}</code></dd></div>
            <div><dt class="inline text-gray-500">Database:</dt> <dd class="inline"><code>{{ $db['database'] }}</code></dd></div>
            <div><dt class="inline text-gray-500">User:</dt> <dd class="inline"><code>{{ $db['username'] ?: '—' }}</code></dd></div>
            <div><dt class="inline text-gray-500">Password:</dt> <dd class="inline">{{ ($db['password_set'] ?? false) ? 'set' : 'empty' }}</dd></div>
        </dl>
    </div>
@endif
