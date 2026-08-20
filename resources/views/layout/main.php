<?php

declare(strict_types=1);

use App\Cart\CartService;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Html as TagHtml;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Title;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var CurrentRoute $currentRoute
 * @var TranslatorInterface $translator
 * @var string $content
 * @var CartService $cart
 * @var Flash $flash
 */

// $this->beginPage() must be the first statement after the docblock —
// matches the ddd-template layout's own note on this Psalm-parser quirk.
$this->beginPage();
$cartCount = count($cart->getItems());
?>
<!DOCTYPE html>
<?php
echo new TagHtml()->lang($currentRoute->getArgument('_language') ?? 'en');
echo Html::openTag('head');
echo Meta::documentEncoding('utf-8');
echo Meta::data('viewport', 'width=device-width, initial-scale=1');
echo new Title()->content('Webshop');
echo Html::tag('link', '', [
    'rel' => 'stylesheet',
    'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
]);
$this->head();
echo Html::closeTag('head');
echo Html::openTag('body');
$this->beginBody();

echo Html::openTag('header');
echo Html::openTag('nav', ['class' => 'navbar navbar-expand-lg bg-body-tertiary border-bottom']);
echo Html::openTag('div', ['class' => 'container-fluid']);
echo Html::a('Webshop', $urlGenerator->generate('catalog/index'), ['class' => 'navbar-brand']);
echo Html::a(
    'Cart (' . $cartCount . ')',
    $urlGenerator->generate('cart/index'),
    ['class' => 'btn btn-outline-primary btn-sm ms-auto'],
);
echo Html::closeTag('div');
echo Html::closeTag('nav');
echo Html::closeTag('header');

echo Html::openTag('main', ['class' => 'container py-4']);
/** @var array<string, list<string>> $flashes */
$flashes = $flash->getAll();
foreach ($flashes as $level => $messages) {
    foreach ($messages as $message) {
        $alertClass = match ($level) {
            'danger', 'warning', 'info', 'success' => $level,
            default => 'info',
        };
        echo Html::div(
            Html::encode($message),
            ['class' => 'alert alert-' . $alertClass],
        )->render();
    }
}
echo $content;
echo Html::closeTag('main');

echo Html::openTag('footer', ['class' => 'border-top py-3 text-center text-muted small']);
echo Html::encode('Webshop — a headless storefront for rossaddison/invoice');
echo Html::closeTag('footer');

echo Html::script('', ['src' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js']);

$this->endBody();
echo Html::closeTag('body');
echo Html::closeTag('html');
$this->endPage(true);
