<div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
    <p class="font-medium">Infrastructure</p>
    <dl class="mt-2 space-y-2">
        <div>
            <dt class="text-emerald-800/80">Database</dt>
            <dd class="mt-0.5">
                {{ $db['connection_label'] }}
                — <code>{{ $db['host'] }}:{{ $db['port'] }}</code> / <code>{{ $db['database'] }}</code>
                @if (filled($db['username'] ?? null))
                    (user <code>{{ $db['username'] }}</code>)
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-emerald-800/80">Cache &amp; search</dt>
            <dd class="mt-0.5">
                @if ($redis['uses_redis'] ?? true)
                    Redis — <code>{{ $redis['host'] }}:{{ $redis['port'] }}</code>
                    (cache <code>{{ $redis['cache_store'] }}</code>, sessions <code>{{ $redis['session_driver'] }}</code>, search <code>{{ $redis['search_driver'] }}</code>)
                @else
                    File-based — cache <code>{{ $redis['cache_store'] }}</code>, sessions <code>{{ $redis['session_driver'] }}</code>, search <code>{{ $redis['search_driver'] }}</code>
                @endif
            </dd>
        </div>
    </dl>
</div>
