<?php

declare(strict_types=1);

namespace App\Cart;

use App\Catalog\ProductCatalogClient;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CartController
{
    private WebViewRenderer $webViewRenderer;

    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly CartService $cartService,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('cart');
    }

    public function index(): ResponseInterface
    {
        return $this->webViewRenderer->render('index', [
            'items' => $this->cartService->getItems(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    /**
     * Re-resolves name/price from the catalog rather than trusting the
     * submitted form — the same "catalog is the only source of truth for
     * price" principle `OrderService` already enforces on the invoice
     * side for the cart-to-order handoff.
     */
    public function add(ServerRequestInterface $request, ProductCatalogClient $catalog): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (float) ($body['quantity'] ?? 1);

        $product = $catalog->getProduct($productId);
        if ($product !== null && $quantity > 0.0) {
            $this->cartService->add($product->id, $product->displayName(), $product->price, $quantity);
        }

        return $this->webService->getRedirectResponse('cart/index');
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (float) ($body['quantity'] ?? 0);

        $this->cartService->updateQuantity($productId, $quantity);

        return $this->webService->getRedirectResponse('cart/index');
    }

    public function remove(#[RouteArgument('id')] int $id): ResponseInterface
    {
        $this->cartService->remove($id);

        return $this->webService->getRedirectResponse('cart/index');
    }
}
