@extends('install.layout')

@section('content')
    <h2 class="text-lg font-semibold mb-4">Server requirements</h2>
    <p class="text-sm text-gray-600 mb-6">Fix the items below before continuing.</p>

    <ul class="space-y-3 mb-6">
        @foreach ($requirements as $requirement)
            <li class="flex items-start gap-3 text-sm">
                <span @class([
                    'mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                    'bg-green-100 text-green-800' => $requirement['ok'],
                    'bg-red-100 text-red-800' => ! $requirement['ok'],
                ])>{{ $requirement['ok'] ? '✓' : '✗' }}</span>
                <div>
                    <p class="font-medium">{{ $requirement['label'] }}</p>
                    @if (! $requirement['ok'] && $requirement['hint'])
                        <p class="text-gray-500 mt-1">{{ $requirement['hint'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    <p class="text-sm text-gray-500">Reload this page after fixing the issues above.</p>
@endsection
