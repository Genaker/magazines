<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>{{ \App\Support\Syndication::siteName() }}</title>
    <subtitle>{{ \App\Support\Syndication::siteDescription() }}</subtitle>
    <link href="{{ route('home') }}"/>
    <link href="{{ route('syndication.atom') }}" rel="self"/>
    <id>{{ route('home') }}</id>
    <updated>{{ ($posts->first()?->published_at ?? now())->toAtomString() }}</updated>
    @foreach ($posts as $post)
        <entry>
            <title>{{ $post->title }}</title>
            <link href="{{ route('posts.show', [$post->authorAlias, $post->slug]) }}"/>
            <id>{{ route('posts.show', [$post->authorAlias, $post->slug]) }}</id>
            <updated>{{ ($post->updated_at ?? $post->published_at)?->toAtomString() }}</updated>
            <published>{{ $post->published_at?->toAtomString() }}</published>
            <summary>{{ $post->excerpt(300) }}</summary>
            <content type="html"><![CDATA[{!! $post->body !!}]]></content>
            <author>
                <name>{{ $post->authorAlias?->name ?? $post->user->name }}</name>
            </author>
        </entry>
    @endforeach
</feed>
