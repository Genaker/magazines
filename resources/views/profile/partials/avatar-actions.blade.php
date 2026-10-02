@props(['user'])

@if ($user->avatar)
    <form method="post" action="{{ \App\Support\SiteUrl::mainRoute('profile.avatar.destroy') }}" {{ $attributes->merge(['class' => 'inline text-sm']) }}>
        @csrf
        @method('delete')
        <button type="submit" class="text-red-600 hover:underline">
            {{ __('app.remove_avatar') }}
        </button>
    </form>
@endif
