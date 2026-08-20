<?php

declare(strict_types=1);

use App\Catalog\Product;
use App\Catalog\ProductFilter;
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
 * @var ProductFilter $filter
 * @var list<string> $categoryOptions
 * @var list<string> $subcategoryOptions
 * @var list<string> $familyOptions
 * @var float|null $catalogMinPrice
 * @var float|null $catalogMaxPrice
 */

// $this->setTitle() must be the first statement after the docblock —
// same narrow Psalm parser quirk noted in the layout's own
// $this->beginPage() comment (see resources/views/layout/main.php).
$this->setTitle('Products');

// Same Bootstrap5\Carousel widget as invoice's own resources/views/site/gallery.php
// — a rotating showcase to browse the range, not the transaction surface
// itself; each slide links through to the product's own detail page,
// where "Add to cart" already lives (a quantity input + form doesn't fit
// naturally inside a carousel slide). Three products per slide, chunked
// in catalog order.
$productsPerSlide = 3;
$tileImageHeight = 220;

// Muted placeholder for products with no photo yet — same shopping-bag
// glyph as the navbar logo, so a mix of real photos and placeholders
// still reads as one consistent design rather than a broken-image icon.
$placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"'
    . ' stroke-linejoin="round" aria-hidden="true" class="text-white-50">'
    . '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>'
    . '<path d="M3 6h18"/>'
    . '<path d="M16 10a4 4 0 0 1-8 0"/>'
    . '</svg>';

/** Renders one product's image/placeholder + name + price, linked through
 * to its own detail page — the unit repeated three-up per slide. */
$renderTile = function (Product $product) use ($urlGenerator, $placeholderSvg, $tileImageHeight): string {
    $media = $product->imageUrl !== null
        ? new Img()
            ->src($product->imageUrl)
            ->alt($product->displayName())
            ->addAttributes(['style' => 'max-height: ' . $tileImageHeight . 'px; object-fit: contain;'])
            ->render()
        : $placeholderSvg;

    $caption = '<div class="text-white mt-2">'
        . Html::encode($product->displayName())
        . ' — ' . number_format($product->price, 2)
        . '</div>';

    return new A()
        ->href($urlGenerator->generate('catalog/show', ['id' => (string) $product->id]))
        ->addClass('d-flex flex-column align-items-center text-decoration-none')
        ->content(
            '<div class="d-flex align-items-center justify-content-center" style="height: '
                . $tileImageHeight . 'px;">' . $media . '</div>' . $caption,
        )
        ->encode(false)
        ->render();
};

/**
 * One checkbox-group `<fieldset>` for the filter sidebar — Category,
 * Subcategory or Family. Options come from the *full* unfiltered catalog
 * (see ProductsController::distinctValues()'s own docblock), so checking
 * one group's box never makes another group's option disappear. Renders
 * nothing for a group with no options at all (e.g. a storefront with no
 * categorized products yet).
 *
 * @param list<string> $options
 * @param list<string> $selected
 */
$renderFilterGroup = function (string $legend, string $paramName, array $options, array $selected): string {
    if ($options === []) {
        return '';
    }

    $checkboxes = '';
    /** @var list<string> $options */
    foreach ($options as $option) {
        $id = $paramName . '-' . md5($option);
        $checkboxes .= '<div class="form-check">'
            . Html::checkbox($paramName . '[]', $option, ['id' => $id, 'class' => 'form-check-input'])
                ->checked(in_array($option, $selected, true))
                ->render()
            . '<label class="form-check-label" for="' . Html::encode($id) . '">'
                . Html::encode($option) . '</label>'
            . '</div>';
    }

    return '<fieldset class="mb-3"><legend class="h6">' . Html::encode($legend) . '</legend>'
        . $checkboxes . '</fieldset>';
};
?>
<div class="row">
    <aside class="col-md-3 mb-4">
        <h2 class="h6 mb-3">Filters</h2>
        <?= Html::openTag('form', ['method' => 'get', 'action' => $urlGenerator->generate('catalog/index')]) ?>
        <fieldset class="mb-3">
            <legend class="h6">Price</legend>
            <div class="d-flex align-items-center gap-2">
                <?= Html::input(
                    'number',
                    'min_price',
                    $filter->minPrice !== null ? (string) $filter->minPrice : null,
                    [
                        'class' => 'form-control form-control-sm',
                        'placeholder' => $catalogMinPrice !== null ? number_format($catalogMinPrice, 2) : 'Min',
                        'min' => '0',
                        'step' => '0.01',
                        'aria-label' => 'Minimum price',
                    ],
                ) ?>
                <span class="text-muted">–</span>
                <?= Html::input(
                    'number',
                    'max_price',
                    $filter->maxPrice !== null ? (string) $filter->maxPrice : null,
                    [
                        'class' => 'form-control form-control-sm',
                        'placeholder' => $catalogMaxPrice !== null ? number_format($catalogMaxPrice, 2) : 'Max',
                        'min' => '0',
                        'step' => '0.01',
                        'aria-label' => 'Maximum price',
                    ],
                ) ?>
            </div>
        </fieldset>
        <?= $renderFilterGroup('Category', 'category', $categoryOptions, $filter->categories) ?>
        <?= $renderFilterGroup('Subcategory', 'subcategory', $subcategoryOptions, $filter->subcategories) ?>
        <?= $renderFilterGroup('Family', 'family', $familyOptions, $filter->families) ?>
        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">Apply filters</button>
        <?php if (!$filter->isEmpty()): ?>
        <?= Html::a('Clear filters', $urlGenerator->generate('catalog/index'), ['class' => 'btn btn-link btn-sm w-100']) ?>
        <?php endif; ?>
        <?= Html::closeTag('form') ?>
    </aside>
    <section class="col-md-9">
        <h1 class="mb-4">Products</h1>
        <?php if ($products === []): ?>
            <p class="text-muted">No products match those filters.</p>
        <?php else: ?>
        <?php
        $items = [];
        foreach (array_chunk($products, $productsPerSlide) as $slideIndex => $slideProducts) {
            $tiles = array_map($renderTile, $slideProducts);

            $content = '<div class="bg-dark d-flex align-items-start justify-content-around gap-3 p-4">'
                . implode('', $tiles) . '</div>';

            $items[] = CarouselItem::to(
                content: $content,
                active: $slideIndex === 0,
            );
        }
        echo Carousel::widget()->items(...$items)->render();
        ?>
        <?php endif; ?>
    </section>
</div>
