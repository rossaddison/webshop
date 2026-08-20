<?php

declare(strict_types=1);

use App\Cart\CartItem;
use App\Checkout\CheckoutForm;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Csrf $csrf
 * @var CheckoutForm $form
 * @var list<CartItem> $items
 * @var float $total
 */

$this->setTitle('Checkout');
?>
<h1 class="mb-4">Checkout</h1>

<h2 class="h5">Order summary</h2>
<ul class="list-group mb-4">
    <?php foreach ($items as $item): ?>
    <li class="list-group-item d-flex justify-content-between">
        <span><?= Html::encode($item->name) ?> &times; <?= $item->quantity ?></span>
        <span><?= number_format($item->subtotal(), 2) ?></span>
    </li>
    <?php endforeach; ?>
    <li class="list-group-item d-flex justify-content-between fw-bold">
        <span>Total</span>
        <span><?= number_format($total, 2) ?></span>
    </li>
</ul>

<?= new Form()
    ->post($urlGenerator->generate('checkout/submit'))
    ->csrf($csrf)
    ->open() ?>
<?= Field::errorSummary($form)->header('') ?>
<?= Field::text($form, 'name')->hideLabel()->placeholder('First name') ?>
<?= Field::text($form, 'surname')->hideLabel()->placeholder('Last name') ?>
<?= Field::text($form, 'email')->hideLabel()->placeholder('Email')->addInputAttributes(['type' => 'email']) ?>
<?= Field::text($form, 'address1')->hideLabel()->placeholder('Address line 1') ?>
<?= Field::text($form, 'address2')->hideLabel()->placeholder('Address line 2') ?>
<?= Field::text($form, 'city')->hideLabel()->placeholder('City') ?>
<?= Field::text($form, 'zip')->hideLabel()->placeholder('Postal / ZIP code') ?>
<?= Field::text($form, 'country')->hideLabel()->placeholder('Country') ?>
<?= Field::text($form, 'phone')->hideLabel()->placeholder('Phone') ?>
<?= Field::submitButton()->content('Place order') ?>
<?= new Form()->close() ?>
