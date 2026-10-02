@if ($items->isNotEmpty())
    <div class="portfolio-gallery post-gallery mb-8" data-component="gallery">
        @foreach ($items as $item)
            @php
                $alt = $item->altText(__('app.gallery_photo_alt_fallback', [
                    'title' => $postTitle,
                    'number' => $loop->iteration,
                ]));
            @endphp
            <figure class="portfolio-gallery__item">
                <a href="{{ $item->url('lg') }}"
                   class="portfolio-gallery__link"
                   data-src="{{ $item->url('lg') }}"
                   @if ($item->caption) data-sub-html="{{ e($item->caption) }}" @endif>
                    <img src="{{ $item->url('md') }}"
                         @if ($item->srcset()) srcset="{{ $item->srcset() }}" sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw" @endif
                         alt="{{ $alt }}"
                         loading="lazy"
                         decoding="async"
                         class="portfolio-gallery__image">
                </a>
                @if ($item->caption)
                    <figcaption class="portfolio-gallery__caption">{{ $item->caption }}</figcaption>
                @endif
            </figure>
        @endforeach
    </div>
@endif
