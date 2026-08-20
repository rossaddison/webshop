<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ProductsController
{
    private WebViewRenderer $webViewRenderer;

    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('catalog');
    }

    public function index(ProductCatalogClient $catalog): ResponseInterface
    {
        return $this->webViewRenderer->render('index', ['products' => $catalog->listProducts()]);
    }

    public function show(#[RouteArgument('id')] int $id, ProductCatalogClient $catalog): ResponseInterface
    {
        $product = $catalog->getProduct($id);
        if ($product === null) {
            return $this->webService->getNotFoundResponse();
        }

        return $this->webViewRenderer->render('view', ['product' => $product]);
    }
}
