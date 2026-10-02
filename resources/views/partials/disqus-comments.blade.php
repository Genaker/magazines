@php
    $shortname = \App\Support\CommentSettings::shortname();
    $pageUrl = route('posts.show', [$post->authorAlias, $post->slug], absolute: true);
    $pageIdentifier = 'post-'.$post->id;
@endphp

<section id="comments" class="mt-10 pt-8 border-t border-gray-100 pb-4">
    <h2 class="text-2xl font-bold mb-6">Discussion</h2>

    <div id="disqus_thread"></div>
    <noscript>
        Please enable JavaScript to view the
        <a href="https://disqus.com/?ref_noscript" rel="noopener noreferrer">comments powered by Disqus.</a>
    </noscript>
</section>

@push('scripts')
    <script>
        var disqus_config = function () {
            this.page.url = @json($pageUrl);
            this.page.identifier = @json($pageIdentifier);
            this.page.title = @json($post->title);
        };
        (function () {
            var d = document, s = d.createElement('script');
            s.src = 'https://{{ $shortname }}.disqus.com/embed.js';
            s.setAttribute('data-timestamp', +new Date());
            (d.head || d.body).appendChild(s);
        })();
    </script>
@endpush
