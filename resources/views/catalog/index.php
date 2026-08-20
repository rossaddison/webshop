<?php

declare(strict_types=1);

use App\Catalog\Product;
use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var list<Product> $products
 */

// $this->setTitle() must be the first statement after the docblock —
// same narrow Psalm parser quirk noted in the layout's own
// $this->beginPage() comment (see resources/views/layout/main.php).
$this->setTitle('Products');

// Same Bootstrap5\Carousel widget as invoice's own resources/views/site/gallery.php
// — a rotating showcase to browse the range, not the transaction surface
// itself; each slide links through to the product's own detail page,
// where "Add to cart" already lives (a quantity input + form doesn't fit
// naturally inside a carousel slide).
$slideHeight = 400;

// Muted placeholder for products with no photo yet — same shopping-bag
// glyph as the navbar logo, so a mix of real photos and placeholders
// still reads as one consistent design rather than a broken-image icon.
$placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"'
    . ' stroke-linejoin="round" aria-hidden="true" class="text-white-50">'
    . '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>'
    . '<path d="M3 6h18"/>'
    . '<path d="M16 10a4 4 0 0 1-8 0"/>'
    . '</svg>';
?>
<h1 class="mb-4">Products</h1>
<?php if ($products === []): ?>
    <p class="text-muted">No products available right now.</p>
<?php else: ?>
<?php
$items = [];
foreach ($products as $index => $product) {
    $detailUrl = $urlGenerator->generate('catalog/show', ['id' => (string) $product->id]);

    $media = $product->imageUrl !== null
        ? new Img()
            ->src($product->imageUrl)
            ->alt($product->displayName())
            ->addAttributes(['style' => 'max-height: ' . $slideHeight . 'px; object-fit: contain;'])
            ->render()
        : $placeholderSvg;

    $slide = new A()
        ->href($detailUrl)
        ->content($media)
        ->encode(false)
        ->render();

    $content = '<div class="bg-dark d-flex align-items-center justify-content-center"'
        . ' style="height: ' . $slideHeight . 'px;">' . $slide . '</div>';

    $caption = Html::encode($product->displayName()) . ' — ' . number_format($product->price, 2);

    $items[] = CarouselItem::to(
        content: $content,
        caption: $caption,
        active: $index === 0,
        encodeCaption: false,
        captionAttributes: ['class' => ['d-none', 'd-md-block']],
    );
}
echo Carousel::widget()->items(...$items)->render();
?>
<?php endif; ?>
