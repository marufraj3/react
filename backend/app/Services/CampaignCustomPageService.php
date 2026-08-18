<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Prepares admin-authored landing-page source for safe storage and rendering.
 *
 * Custom JavaScript is intentionally supported because only authenticated admins can
 * edit it. PHP/Blade directives are removed so uploaded source can never become
 * server-side executable code.
 */
class CampaignCustomPageService
{
    /** @var array<int, string> */
    public const TOKENS = [
        'product_name', 'product_price', 'old_price', 'description',
        'featured_image', 'stock', 'reviews', 'products', 'checkout',
    ];

    public function cleanHtml(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = str_replace("\0", '', $html);
        $html = preg_replace('/<\?(?:php|=)?[\s\S]*?\?>/i', '', $html) ?? '';

        // Park external resources (stylesheets, fonts, CDN scripts) before the body is
        // extracted — they usually live in <head> and would otherwise be lost. They are
        // re-attached at the top of the returned markup so the design loads as authored.
        $resources = [];
        $html = preg_replace_callback('#<link\b[^>]*>|<script\b[^>]*\bsrc\s*=[^>]*>[\s\S]*?</script\s*>#i', function (array $match) use (&$resources): string {
            return $this->maskResource($match[0], $resources);
        }, $html) ?? '';

        // A complete uploaded document is accepted; only its body belongs inside the storefront shell.
        if (preg_match('#<body\b[^>]*>([\s\S]*?)</body\s*>#i', $html, $body)) {
            $html = $body[1];
        }

        // Structural tags are removed; they belong to the storefront shell. Inline
        // <style>/<script> content has already been extracted into the CSS/JavaScript
        // drafts by prepareSource()/splitHtmlUpload(), so removing the empty wrappers
        // never drops authored code.
        $html = preg_replace('#</?(?:html|head|body|title|meta|base)\b[^>]*>#i', '', $html) ?? '';
        $html = preg_replace('#<script\b[^>]*>[\s\S]*?</script\s*>#i', '', $html) ?? '';
        $html = preg_replace('#<style\b[^>]*>[\s\S]*?</style\s*>#i', '', $html) ?? '';

        // Keep only documented {{ variables }}. Unknown Blade expressions and all
        // directives are removed rather than evaluated.
        $preserved = [];
        $html = preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/i', function (array $match) use (&$preserved): string {
            $token = strtolower($match[1]);
            if (!in_array($token, self::TOKENS, true)) {
                return '';
            }
            $key = '___CUSTOM_TOKEN_' . count($preserved) . '___';
            $preserved[$key] = '{{ ' . $token . ' }}';
            return $key;
        }, $html) ?? '';
        $html = preg_replace('/\{!![\s\S]*?(?:!!\}|$)/', '', $html) ?? '';
        $html = preg_replace('/\{\{[\s\S]*?(?:\}\}|$)/', '', $html) ?? '';
        $html = preg_replace('/@(php|endphp|include|extends|section|yield|inject|foreach|endforeach|for|endfor|while|endwhile|if|elseif|else|endif|unless|endunless|auth|endauth|guest|endguest|can|endcan)\b(?:\s*\([^\r\n)]*\))?/i', '', $html) ?? '';

        if ($preserved !== []) {
            $html = strtr($html, $preserved);
        }

        // Restore resources that lived inside the body in place; resources that lived in
        // <head> (dropped by the body extraction) are re-attached at the very top so
        // CDN stylesheets/scripts load before the content they style.
        if ($resources !== []) {
            $inBody = [];
            $inHead = [];
            foreach ($resources as $index => $resource) {
                $key = '___CPB_RESOURCE_' . $index . '___';
                if (str_contains($html, $key)) {
                    $inBody[$key] = $resource;
                } else {
                    $inHead[] = $resource;
                }
            }
            if ($inBody !== []) {
                $html = strtr($html, $inBody);
            }
            if ($inHead !== []) {
                $html = implode("\n", $inHead) . "\n" . $html;
            }
        }

        return trim($html) === '' ? null : trim($html);
    }

    /**
     * Safely park an external resource tag while the rest of the markup is processed,
     * then restore it verbatim at the end. Prevents a <link>/<script src> tag from
     * being swallowed by the structural/Blade clean-up below.
     */
    private function maskResource(string $resource, array &$resources): string
    {
        $index = count($resources);
        $resources[] = $resource;
        return '___CPB_RESOURCE_' . $index . '___';
    }

    /**
     * Turn a raw source blob (a full HTML document pasted into the HTML editor or an
     * uploaded file) into the three builder drafts without losing any authored code:
     * inline <style> is merged into the CSS draft, inline <script> into the JS draft,
     * and external <link>/<script src> resources stay inside the HTML.
     *
     * @param  string|null  $html
     * @param  string|null  $css
     * @param  string|null  $js
     * @return array{html:?string,css:?string,js:?string}
     */
    public function prepareSource(?string $html, ?string $css = null, ?string $js = null): array
    {
        $html = (string) ($html ?? '');
        $extractedCss = [];
        $extractedJs = [];

        // Pull inline styles out of the markup so nothing typed in the HTML editor is lost.
        $html = preg_replace_callback('#<style\b[^>]*>([\s\S]*?)</style\s*>#i', function (array $match) use (&$extractedCss): string {
            $extractedCss[] = trim($match[1]);
            return '';
        }, $html) ?? $html;

        // Pull inline scripts out; external <script src> (CDN libraries) stays in place.
        $html = preg_replace_callback('#<script\b(?![^>]*\bsrc\s*=)[^>]*>([\s\S]*?)</script\s*>#i', function (array $match) use (&$extractedJs): string {
            $extractedJs[] = trim($match[1]);
            return '';
        }, $html) ?? $html;

        $cssParts = array_values(array_filter(array_map('trim', array_merge($extractedCss, [(string) ($css ?? '')]))));
        $jsParts = array_values(array_filter(array_map('trim', array_merge($extractedJs, [(string) ($js ?? '')]))));

        return [
            'html' => $this->cleanHtml($html),
            'css' => $this->cleanCss(implode("\n\n", $cssParts)),
            'js' => $this->cleanJs(implode("\n\n", $jsParts)),
        ];
    }

    public function cleanCss(?string $css): ?string
    {
        if ($css === null || trim($css) === '') {
            return null;
        }

        $css = str_replace("\0", '', $css);
        $css = preg_replace('#</?(?:style|script)\b[^>]*>#i', '', $css) ?? '';
        $css = preg_replace('/<\?(?:php|=)?[\s\S]*?\?>/i', '', $css) ?? '';
        $css = preg_replace('/\{\{[\s\S]*?(?:\}\}|$)|\{!![\s\S]*?(?:!!\}|$)/', '', $css) ?? '';
        // Block legacy CSS execution primitives while preserving modern custom CSS.
        $css = preg_replace('/(?:expression\s*\(|javascript\s*:|vbscript\s*:|-moz-binding\s*:|behavior\s*:)/i', '', $css) ?? '';

        return trim($css) === '' ? null : trim($css);
    }

    public function cleanJs(?string $javascript): ?string
    {
        if ($javascript === null || trim($javascript) === '') {
            return null;
        }

        $javascript = str_replace("\0", '', $javascript);
        $javascript = preg_replace('#^\s*<script\b[^>]*>|</script\s*>\s*$#i', '', $javascript) ?? '';
        $javascript = preg_replace('/<\?(?:php|=)?[\s\S]*?\?>/i', '', $javascript) ?? '';
        $javascript = preg_replace('/\{!![\s\S]*?(?:!!\}|$)|\{\{[\s\S]*?(?:\}\}|$)/', '', $javascript) ?? '';

        // It will be emitted inside a script element; neutralize all closing sequences.
        $javascript = preg_replace('#</script#i', '<\\/script', $javascript) ?? '';

        return trim($javascript) === '' ? null : trim($javascript);
    }

    /**
     * Split a complete HTML upload into the three builder editors.
     *
     * @return array{html:?string,css:?string,js:?string}
     */
    public function splitHtmlUpload(string $source): array
    {
        return $this->prepareSource($source);
    }

    /**
     * The built-in "AI Studio" conversion landing page (Tailwind + Font Awesome),
     * shipped as a ready-to-edit starting point. Its product names, prices, images,
     * product selector and checkout are dynamic, so everything stays controllable
     * from the admin panel while the visual design matches the provided reference.
     *
     * @return array{html:?string,css:?string,js:?string}
     */
    public function studioTemplate(): array
    {
        $path = base_path('resources/templates/ai-studio-landing.html');
        $source = is_file($path) ? (string) file_get_contents($path) : '';

        if (trim($source) === '') {
            return ['html' => null, 'css' => null, 'js' => null];
        }

        return $this->splitHtmlUpload($source);
    }

    public function render(?string $html, Campaign $campaign, Collection $products): string
    {
        $product = $products->first();
        $stock = $this->stockFor($product);
        $description = strip_tags((string) ($campaign->short_description ?: $campaign->description));
        $image = $product
            ? asset(optional($product->image)->image ?? 'public/uploads/default.webp')
            : ($campaign->image_one ? asset($campaign->image_one) : '');

        $tokens = [
            '{{ product_name }}' => e($product?->name ?? $campaign->name),
            '{{ product_price }}' => e(number_format((float) ($product?->new_price ?? 0), 0)),
            '{{ old_price }}' => e(number_format((float) ($product?->old_price ?? 0), 0)),
            '{{ description }}' => e($description),
            '{{ featured_image }}' => e($image),
            '{{ stock }}' => e((string) $stock),
            '{{ reviews }}' => '<div class="cpb-live-reviews" data-cpb-dynamic="reviews"></div>',
            '{{ products }}' => '<div class="cpb-live-products" data-cpb-dynamic="products"></div>',
            '{{ checkout }}' => '<div data-cpb-dynamic="checkout"></div>',
        ];

        $rendered = strtr((string) $html, $tokens);

        // Product selection and the authoritative Laravel checkout can never disappear
        // accidentally. Authors can position them precisely with the documented tokens.
        if (!str_contains($rendered, 'data-cpb-dynamic="products"') && !str_contains($rendered, "data-cpb-dynamic='products'")) {
            $rendered .= '<section class="cpb-custom-fallback"><div class="cpb-custom-fallback-inner"><div class="cpb-live-products" data-cpb-dynamic="products"></div></div></section>';
        }
        if (!str_contains($rendered, 'data-cpb-dynamic="checkout"') && !str_contains($rendered, "data-cpb-dynamic='checkout'")) {
            $rendered .= '<section class="cpb-custom-fallback"><div class="cpb-custom-fallback-inner"><div data-cpb-dynamic="checkout"></div></div></section>';
        }

        return $rendered;
    }

    private function stockFor(?Product $product): int
    {
        if (!$product) {
            return 0;
        }

        $variants = collect($product->variantPrices ?? []);
        if ($variants->contains(fn ($variant) => $variant->stock !== null)) {
            return (int) $variants->sum(fn ($variant) => max(0, (int) $variant->stock));
        }

        return max(0, (int) ($product->stock ?? 0));
    }
}
