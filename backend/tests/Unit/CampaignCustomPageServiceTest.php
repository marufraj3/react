<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Product;
use App\Services\CampaignCustomPageService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CampaignCustomPageServiceTest extends TestCase
{
    public function test_complete_html_upload_is_split_into_clean_editors(): void
    {
        $service = new CampaignCustomPageService();
        $source = <<<'HTML'
<!doctype html><html><head>
<style>.hero { color: red; }</style>
<script>window.previewReady = true;</script>
<script src="https://example.com/remote.js"></script>
</head><body><h1>{{ product_name }}</h1><?php echo 'unsafe'; ?></body></html>
HTML;

        $result = $service->splitHtmlUpload($source);

        $this->assertStringContainsString('<h1>{{ product_name }}</h1>', $result['html']);
        $this->assertStringContainsString('.hero { color: red; }', $result['css']);
        $this->assertStringContainsString('window.previewReady = true;', $result['js']);
        $this->assertStringNotContainsString('remote.js', $result['js']);
        // External <script src> resources are preserved (not silently dropped).
        $this->assertStringContainsString('https://example.com/remote.js', $result['html']);
        $this->assertStringNotContainsString('<?php', $result['html']);
    }

    public function test_inline_style_and_script_are_merged_not_dropped(): void
    {
        $service = new CampaignCustomPageService();
        $result = $service->prepareSource(
            '<style>.x{color:red}</style><div class="x">Hi</div><script>alert(1)</script>',
            '/* existing css */',
            '// existing js'
        );

        $this->assertStringContainsString('<div class="x">Hi</div>', $result['html']);
        $this->assertStringContainsString('.x{color:red}', $result['css']);
        $this->assertStringContainsString('alert(1)', $result['js']);
        $this->assertStringContainsString('/* existing css */', $result['css']);
        $this->assertStringContainsString('// existing js', $result['js']);
        $this->assertStringNotContainsString('<style', $result['html']);
        $this->assertStringNotContainsString('<script', $result['html']);
    }

    public function test_head_resources_are_reattached_to_body_markup(): void
    {
        $service = new CampaignCustomPageService();
        $source = <<<'HTML'
<html><head>
<link rel="stylesheet" href="https://cdn.example.com/icons.css">
<script src="https://cdn.example.com/tailwind.js"></script>
</head><body><p class="text-red-600">Hello</p></body></html>
HTML;

        $result = $service->splitHtmlUpload($source);

        $this->assertStringContainsString('https://cdn.example.com/icons.css', $result['html']);
        $this->assertStringContainsString('https://cdn.example.com/tailwind.js', $result['html']);
        $this->assertStringContainsString('<p class="text-red-600">Hello</p>', $result['html']);
    }

    public function test_live_tokens_are_replaced_and_commerce_is_never_omitted(): void
    {
        $service = new CampaignCustomPageService();
        $campaign = new Campaign([
            'name' => 'Summer Campaign',
            'short_description' => '<b>Soft cotton</b>',
        ]);
        $product = new Product([
            'id' => 15,
            'name' => 'Premium T-Shirt',
            'new_price' => 990,
            'old_price' => 1490,
            'stock' => 8,
        ]);
        $product->setRelation('variantPrices', new Collection());

        $html = $service->render(
            '<h1>{{ product_name }}</h1><p>৳{{ product_price }}</p><div>{{ reviews }}</div>',
            $campaign,
            collect([$product])
        );

        $this->assertStringContainsString('Premium T-Shirt', $html);
        $this->assertStringContainsString('৳990', $html);
        $this->assertStringContainsString('data-cpb-dynamic="reviews"', $html);
        $this->assertStringContainsString('data-cpb-dynamic="products"', $html);
        $this->assertStringContainsString('data-cpb-dynamic="checkout"', $html);
    }

    public function test_unknown_blade_and_php_are_not_stored(): void
    {
        $service = new CampaignCustomPageService();
        $clean = $service->cleanHtml(
            '@include("secret") {{ config("app.key") }} {{ stock }} {!! $danger !!} <?php phpinfo(); ?>'
        );

        $this->assertSame('{{ stock }}', $clean);
    }
}
