<x-admin-layout>
    <x-admin.data-grid
        title="User reports"
        :paginator="$reports"
        :search="$search"
        search-placeholder="Search reason or user…"
        :sort="$sort"
        :dir="$dir"
    >
        <x-slot:head>
            <th class="px-4 py-3 text-left">Reported</th>
            <th class="px-4 py-3 text-left">Reporter</th>
            <th class="px-4 py-3 text-left">Reason</th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="status" label="Status" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="created_at" label="Reported at" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-right">Actions</th>
        </x-slot:head>

        @foreach ($reports as $report)
            <tr class="hover:bg-gray-50 align-top">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.users.edit', $report->reported) }}" class="font-medium text-indigo-600 hover:underline">
                        {{ $report->reported->name }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $report->reporter->name }}</td>
                <td class="px-4 py-3 text-gray-700 max-w-md">
                    <p>{{ Str::limit($report->reason, 120) }}</p>
                    @if ($report->admin_note)
                        <p class="text-xs text-gray-500 mt-1">Note: {{ $report->admin_note }}</p>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $report->status->value }}</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $report->created_at->format('M j, Y g:i A') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    @if ($report->status === \App\Enums\UserReportStatus::Pending)
                        <form method="POST" action="{{ route('admin.user-reports.dismiss', $report) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:underline mr-2">Dismiss</button>
                        </form>
                        <form method="POST" action="{{ route('admin.user-reports.ban', $report) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:underline">Ban</button>
                        </form>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.data-grid>
</x-admin-layout>
