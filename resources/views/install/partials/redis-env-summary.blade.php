@if ($redis['from_env'] ?? false)
    <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
        <p class="font-medium text-gray-900">Configured in <code class="text-xs bg-white px-1 rounded">.env</code></p>
        <dl class="mt-2 grid gap-1 sm:grid-cols-2">
            <div><dt class="inline text-gray-500">Host:</dt> <dd class="inline"><code>{{ $redis['host'] }}:{{ $redis['port'] }}</code></dd></div>
            <div><dt class="inline text-gray-500">Password:</dt> <dd class="inline">{{ ($redis['password_set'] ?? false) ? 'set' : 'empty' }}</dd></div>
            <div><dt class="inline text-gray-500">Cache:</dt> <dd class="inline"><code>{{ $redis['cache_store'] }}</code></dd></div>
            <div><dt class="inline text-gray-500">Sessions:</dt> <dd class="inline"><code>{{ $redis['session_driver'] }}</code></dd></div>
            <div><dt class="inline text-gray-500">Search:</dt> <dd class="inline"><code>{{ $redis['search_driver'] }}</code></dd></div>
        </dl>
    </div>
@elseif (! ($redis['uses_redis'] ?? true))
    <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
        <p class="font-medium text-gray-900">Configured in <code class="text-xs bg-white px-1 rounded">.env</code></p>
        <p class="mt-1">File-based cache — cache <code>{{ $redis['cache_store'] }}</code>, sessions <code>{{ $redis['session_driver'] }}</code>, search <code>{{ $redis['search_driver'] }}</code>.</p>
    </div>
@endif
