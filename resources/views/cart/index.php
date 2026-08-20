<?php

declare(strict_types=1);

use App\Cart\CartItem;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Csrf $csrf
 * @var list<CartItem> $items
 * @var float $total
 */

$this->setTitle('Cart');
?>
<h1 class="mb-4">Your cart</h1>
<?php if ($items === []): ?>
    <p class="text-muted">Your cart is empty.</p>
    <?= Html::a('Browse products', $urlGenerator->generate('catalog/index'), ['class' => 'btn btn-primary']) ?>
<?php else: ?>
<table class="table align-middle">
    <thead>
        <tr>
            <th>Product</th>
            <th>Price</th>
            <th style="width: 8rem;">Quantity</th>
            <th>Subtotal</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= Html::encode($item->name) ?></td>
            <td><?= number_format($item->price, 2) ?></td>
            <td>
                <?= new Form()
                    ->post($urlGenerator->generate('cart/update'))
                    ->csrf($csrf)
                    ->addAttributes(['class' => 'd-flex gap-2'])
                    ->open() ?>
                <?= Html::hiddenInput('product_id', (string) $item->productId) ?>
                <?= Html::input('number', 'quantity', (string) $item->quantity, ['min' => '0', 'step' => '1', 'class' => 'form-control form-control-sm', 'style' => 'width: 5rem;']) ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Update</button>
                <?= new Form()->close() ?>
            </td>
            <td><?= number_format($item->subtotal(), 2) ?></td>
            <td>
                <?= new Form()
                    ->post($urlGenerator->generate('cart/remove', ['id' => (string) $item->productId]))
                    ->csrf($csrf)
                    ->open() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                <?= new Form()->close() ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-end">Total</th>
            <th><?= number_format($total, 2) ?></th>
            <th></th>
        </tr>
    </tfoot>
</table>
<?= Html::a('Checkout', $urlGenerator->generate('checkout/index'), ['class' => 'btn btn-primary']) ?>
<?php endif; ?>
