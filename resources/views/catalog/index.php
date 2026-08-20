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
 * @var list<Product> $products
 */

$this->setTitle('Products');
?>
<h1 class="mb-4">Products</h1>
<?php if ($products === []): ?>
    <p class="text-muted">No products available right now.</p>
<?php else: ?>
<div class="row row-cols-1 row-cols-md-3 g-4">
    <?php foreach ($products as $product): ?>
    <div class="col">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">
                    <?= Html::a(
                        Html::encode($product->displayName()),
                        $urlGenerator->generate('catalog/show', ['id' => (string) $product->id]),
                    ) ?>
                </h5>
                <?php if ($product->description !== null && $product->description !== ''): ?>
                <p class="card-text text-muted"><?= Html::encode($product->description) ?></p>
                <?php endif; ?>
                <p class="card-text fw-bold"><?= number_format($product->price, 2) ?></p>
                <?= new Form()
                    ->post($urlGenerator->generate('cart/add'))
                    ->csrf($csrf)
                    ->open() ?>
                <?= Html::hiddenInput('product_id', (string) $product->id) ?>
                <?= Html::input('number', 'quantity', '1', ['min' => '1', 'step' => '1', 'class' => 'form-control mb-2', 'style' => 'width: 6rem;']) ?>
                <button type="submit" class="btn btn-primary btn-sm">Add to cart</button>
                <?= new Form()->close() ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
