<x-admin-layout>
    <x-admin.data-grid
        title="Category requests"
        :paginator="$requests"
        :search="$search"
        search-placeholder="Search category name, reason, or user…"
        :sort="$sort"
        :dir="$dir"
    >
        <x-slot:head>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="name" label="Category" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left">Requested by</th>
            <th class="px-4 py-3 text-left">Reason</th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="status" label="Status" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-left"><x-admin.sort-link column="created_at" label="Submitted" :sort="$sort" :dir="$dir" /></th>
            <th class="px-4 py-3 text-right">Actions</th>
        </x-slot:head>

        @foreach ($requests as $request)
            <tr class="hover:bg-gray-50 align-top">
                <td class="px-4 py-3 font-medium text-gray-900">{{ $request->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $request->user->name }}</td>
                <td class="px-4 py-3 text-gray-700 max-w-md">{{ Str::limit($request->reason, 100) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $request->status->value }}</td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $request->created_at->format('M j, Y') }}</td>
                <td class="px-4 py-3 text-right">
                    @if ($request->status->value === 'pending')
                        <form method="POST" action="{{ route('admin.category-requests.approve', $request) }}" class="inline-flex flex-col items-end gap-2 text-left">
                            @csrf
                            <select name="parent_id" class="text-sm border rounded px-2 w-44">
                                <option value="">— Top level —</option>
                                @foreach ($parentOptions as $option)
                                    <option value="{{ $option->id }}">{{ str_repeat('— ', $option->depth) }}{{ $option->name }}</option>
                                @endforeach
                            </select>
                            @if ($magazines->isNotEmpty())
                                <select name="magazine_id" class="text-sm border rounded px-2 w-44">
                                    <option value="">— Site section —</option>
                                    @foreach ($magazines as $magazine)
                                        <option value="{{ $magazine->id }}">{{ $magazine->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="submit" class="text-green-700 hover:underline">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.category-requests.reject', $request) }}" class="inline-flex items-center gap-2">
                            @csrf
                            <input type="text" name="admin_note" placeholder="Rejection reason" class="text-sm border rounded px-2 w-36" required>
                            <button type="submit" class="text-red-700 hover:underline">Reject</button>
                        </form>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.data-grid>
</x-admin-layout>
