<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 */

$this->setTitle('Not found');
?>
<div class="text-center py-5">
    <h1>404</h1>
    <p class="text-muted">That page doesn't exist.</p>
    <?= Html::a('Back to products', $urlGenerator->generate('catalog/index'), ['class' => 'btn btn-primary']) ?>
</div>
