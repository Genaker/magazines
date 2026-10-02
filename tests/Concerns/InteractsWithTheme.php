<?php

namespace Tests\Concerns;

use App\Support\Theme;
use Illuminate\Support\Facades\File;

trait InteractsWithTheme
{
    protected string $themeName = 'integration-theme';

    /** Enable a disposable theme folder under templates/ and register its view path. */
    protected function activateTheme(?string $name = null): string
    {
        $this->themeName = $name ?? $this->themeName;
        $root = base_path('templates/'.$this->themeName);
        File::ensureDirectoryExists($root);

        config(['theme.name' => $this->themeName]);
        Theme::register();

        return $root;
    }

    protected function writeThemeView(string $relativePath, string $contents): void
    {
        $path = base_path('templates/'.$this->themeName.'/'.$relativePath);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    protected function removeTheme(): void
    {
        File::deleteDirectory(base_path('templates/'.$this->themeName));
        config(['theme.name' => '']);
    }

    protected function themeFooterMarker(): string
    {
        return 'THEME-FOOTER-INTEGRATION-MARKER';
    }

    protected function installThemeFooterOverride(): void
    {
        $marker = $this->themeFooterMarker();

        $this->writeThemeView('partials/footer.blade.php', <<<BLADE
<footer data-theme-override class="w-full border-t border-gray-200 bg-white mt-auto">
    <div class="w-full px-4 py-6 text-center text-sm text-gray-500">
        <p>{$marker}</p>
    </div>
</footer>
BLADE);
    }

    protected function installThemePostCardOverride(): void
    {
        $this->writeThemeView('partials/post-card.blade.php', <<<'BLADE'
<article data-theme-post-card class="border-b border-gray-200 py-4">
    <h2 class="text-lg font-bold">
        <a href="{{ route('posts.show', [$post->authorAlias, $post->slug]) }}" class="hover:underline">
            {{ $post->title }}
        </a>
    </h2>
</article>
BLADE);
    }

    protected function installThemePostAuthorOverride(): void
    {
        $this->writeThemeView('partials/post-author.blade.php', <<<'BLADE'
<span data-theme-post-author>{{ $post->authorAlias->name ?? 'Unknown' }}</span>
BLADE);
    }

    protected function installThemePostShowOverride(): void
    {
        $this->writeThemeView('posts/show.blade.php', <<<'BLADE'
<x-app-layout :seo="$seo ?? null">
    <article data-theme-post-page class="max-w-3xl mx-auto px-4 py-8">
        <h1>{{ $post->title }}</h1>
        <div class="prose max-w-none">{!! $post->body !!}</div>
    </article>
</x-app-layout>
BLADE);
    }
}
