<?php

declare(strict_types=1);

use App\Cart\CartService;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Bootstrap5\Assets\BootstrapAsset;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Html as TagHtml;
use Yiisoft\Html\Tag\Meta;
use Yiisoft\Html\Tag\Style;
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
 * @var AssetManager $assetManager
 */

// $this->beginPage() must be the first statement after the docblock —
// matches the ddd-template layout's own note on this Psalm-parser quirk.
$this->beginPage();
$cartCount = count($cart->getItems());
$siteName = 'Webshop';

// A small inline shopping-bag glyph rather than an image file — no asset
// pipeline needed for a single static icon. stroke="currentColor" so it
// follows the navbar-brand link's own text color automatically.
$logoSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
    . ' stroke-linejoin="round" class="me-2" aria-hidden="true">'
    . '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>'
    . '<path d="M3 6h18"/>'
    . '<path d="M16 10a4 4 0 0 1-8 0"/>'
    . '</svg>';

// Published locally (node_modules/bootstrap -> public/assets) rather than
// loaded from a CDN — a CDN dependency meant the page rendered completely
// unstyled for anyone whose browser couldn't reach cdn.jsdelivr.net,
// confirmed live before this fix.
$assetManager->register(BootstrapAsset::class);
$this->addCssFiles($assetManager->getCssFiles());
$this->addJsFiles($assetManager->getJsFiles());
?>
<!DOCTYPE html>
<?php
echo new TagHtml()->lang($currentRoute->getArgument('_language') ?? 'en');
echo Html::openTag('head');
echo Meta::documentEncoding('utf-8');
echo Meta::data('viewport', 'width=device-width, initial-scale=1');
echo new Title()->content($siteName);
// Bootstrap sizes almost everything (buttons, form-control padding,
// labels, headings) in rem, so scaling the root down from the browser
// default (16px) scales the whole page proportionally rather than
// needing per-component overrides.
echo new Style()->content('html { font-size: 14px; }');
$this->head();
echo Html::closeTag('head');
echo Html::openTag('body');
$this->beginBody();

echo Html::openTag('header');
echo Html::openTag('nav', ['class' => 'navbar navbar-expand-lg bg-body-tertiary border-bottom']);
echo Html::openTag('div', ['class' => 'container-fluid']);
echo new A()
    ->href($urlGenerator->generate('catalog/index'))
    ->addClass('navbar-brand d-flex align-items-center fs-4')
    ->content($logoSvg . Html::encode($siteName))
    ->encode(false)
    ->render();
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

$this->endBody();
echo Html::closeTag('body');
echo Html::closeTag('html');
$this->endPage(true);
