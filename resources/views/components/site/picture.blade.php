@props([
    'src' => null,          // primaire URL (WebP vanuit de media-library, of een placeholder-pad)
    'webp' => null,         // optioneel expliciete WebP-URL
    'fallback' => null,     // optioneel expliciete JPG/PNG-URL
    'alt' => '',
    'position' => null,     // object-position, bv. 'center top'
    'class' => '',
    'imgClass' => '',
    'loading' => 'lazy',
    'fetchpriority' => null,
    'sizes' => null,
])

@php
    use App\Models\WebsiteMedia;

    $webpUrl = $webp;
    $jpgUrl = $fallback;

    if (! $webpUrl && ! $jpgUrl && $src) {
        // 1) Library-media: url = WebP, fallback_url = JPG.
        $media = WebsiteMedia::query()->where('url', $src)->orWhere('fallback_url', $src)->first();

        if ($media) {
            $webpUrl = $media->url;
            $jpgUrl = $media->fallback_url ?: $media->url;
        } elseif (str_starts_with($src, '/')) {
            // 2) Lokale placeholder: zoek veilig naar WebP/JPG-siblings (file_exists,
            //    zodat we nooit een <source> emitten die 404't en de fallback breekt).
            $stem = preg_replace('/\.(jpe?g|png|webp)$/i', '', $src);
            $rel = fn (string $u) => ltrim($u, '/');

            if (file_exists(public_path($rel($stem.'.webp')))) {
                $webpUrl = $stem.'.webp';
            }
            foreach (['jpg', 'jpeg', 'png'] as $ext) {
                if (file_exists(public_path($rel($stem.'.'.$ext)))) {
                    $jpgUrl = $stem.'.'.$ext;
                    break;
                }
            }
        }
    }

    $imgSrc = $jpgUrl ?: $src;
    $dims = WebsiteMedia::dimensionsForUrl($src);
@endphp

@if ($imgSrc || $webpUrl)
    {{-- `block` is bewust: een <picture> is van nature display:inline, waardoor
         overflow-hidden + rounded-* die rechtstreeks op deze class gezet worden
         (bv. in text-media of de review-avatars) de <img> niet clippen. Als block
         clipt de afronding wél, net als bij een omhullende <div>. --}}
    <picture class="block {{ $class }}">
        @if ($webpUrl)
            <source srcset="{{ $webpUrl }}" type="image/webp" @if ($sizes) sizes="{{ $sizes }}" @endif>
        @endif
        <img
            src="{{ $imgSrc ?: $webpUrl }}"
            alt="{{ $alt }}"
            loading="{{ $loading }}"
            decoding="async"
            @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
            @if ($dims) width="{{ $dims['width'] }}" height="{{ $dims['height'] }}" @endif
            @if ($position) style="object-position: {{ $position }};" @endif
            class="{{ $imgClass }}"
        >
    </picture>
@endif
