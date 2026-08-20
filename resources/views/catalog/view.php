<?php

declare(strict_types=1);

use App\Catalog\Product;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Csrf $csrf
 * @var Product $product
 */

$this->setTitle($product->displayName());
?>
<p><?= Html::a('← Back to products', $urlGenerator->generate('catalog/index')) ?></p>
<div class="row g-4">
    <div class="col-md-5">
        <?php if ($product->imageUrl !== null): ?>
        <?= Html::img($product->imageUrl)
            ->alt($product->displayName())
            ->addClass('img-fluid rounded')
            ->addAttributes(['style' => 'aspect-ratio: 1 / 1; object-fit: cover; width: 100%;']) ?>
        <?php else: ?>
        <div
            class="d-flex align-items-center justify-content-center text-muted bg-body-secondary rounded"
            style="aspect-ratio: 1 / 1;"
        >No photo</div>
        <?php endif; ?>
    </div>
    <div class="col-md-7">
        <h1><?= Html::encode($product->displayName()) ?></h1>
        <?php if ($product->sku !== null && $product->sku !== ''): ?>
        <p class="text-muted">SKU: <?= Html::encode($product->sku) ?></p>
        <?php endif; ?>
        <?php if ($product->description !== null && $product->description !== ''): ?>
        <p><?= Html::encode($product->description) ?></p>
        <?php endif; ?>
        <p class="fs-4 fw-bold">
            <?= number_format($product->price, 2) ?>
            <?php if ($product->unit !== null && $product->unit !== ''): ?>
                <span class="fs-6 text-muted">/ <?= Html::encode($product->unit) ?></span>
            <?php endif; ?>
        </p>
        <?= new Form()
            ->post($urlGenerator->generate('cart/add'))
            ->csrf($csrf)
            ->open() ?>
        <?= Html::hiddenInput('product_id', (string) $product->id) ?>
        <div class="input-group mb-3" style="max-width: 12rem;">
            <?= Html::input('number', 'quantity', '1', ['min' => '1', 'step' => '1', 'class' => 'form-control']) ?>
        </div>
        <button type="submit" class="btn btn-primary">Add to cart</button>
        <?= new Form()->close() ?>
    </div>
</div>
