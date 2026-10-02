<x-app-layout>
    <x-profile-layout>
        <h1 class="text-3xl font-bold mb-6">My category requests</h1>

        @foreach ($requests as $request)
            <div class="border-b border-gray-200 py-4">
                <p class="font-medium">{{ $request->name }}</p>
                <p class="text-sm text-gray-500">Status: {{ $request->status->value }}</p>
                @if ($request->admin_note)
                    <p class="text-sm text-gray-600 mt-1">{{ $request->admin_note }}</p>
                @endif
            </div>
        @endforeach

        <div class="mt-6">{{ $requests->links() }}</div>
    </x-profile-layout>
</x-app-layout>
