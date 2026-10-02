<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title>{{ \App\Support\Syndication::siteName() }}</title>
        <link>{{ route('home') }}</link>
        <description>{{ \App\Support\Syndication::siteDescription() }}</description>
        <language>{{ str_replace('_', '-', app()->getLocale()) }}</language>
        <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ route('syndication.rss') }}" rel="self" type="application/rss+xml"/>
        @foreach ($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ route('posts.show', [$post->authorAlias, $post->slug]) }}</link>
                <guid isPermaLink="true">{{ route('posts.show', [$post->authorAlias, $post->slug]) }}</guid>
                <description><![CDATA[{!! $post->excerpt(300) !!}]]></description>
                <content:encoded><![CDATA[{!! $post->body !!}]]></content:encoded>
                <pubDate>{{ $post->published_at?->toRfc2822String() }}</pubDate>
                <author>{{ $post->authorAlias?->name ?? $post->user->name }}</author>
                @if ($image = \App\Support\Syndication::postImageUrl($post))
                    <enclosure url="{{ $image }}" type="image/jpeg"/>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
