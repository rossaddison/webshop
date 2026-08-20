<?php

declare(strict_types=1);

namespace App\Catalog;

use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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

    public function index(ServerRequestInterface $request, ProductCatalogClient $catalog): ResponseInterface
    {
        $allProducts = $catalog->listProducts();
        $filter = ProductFilter::fromQueryParams($request->getQueryParams());

        $prices = array_map(static fn (Product $p): float => $p->price, $allProducts);

        return $this->webViewRenderer->render('index', [
            'products' => $filter->apply($allProducts),
            'filter' => $filter,
            'categoryOptions' => $this->distinctValues($allProducts, static fn (Product $p): ?string => $p->category),
            'subcategoryOptions' => $this->distinctValues($allProducts, static fn (Product $p): ?string => $p->subcategory),
            'familyOptions' => $this->distinctValues($allProducts, static fn (Product $p): ?string => $p->family),
            // The full catalog's own price range — shown as the input
            // fields' placeholder text, so the customer knows what range
            // is even worth typing before they've filtered anything.
            'catalogMinPrice' => $prices === [] ? null : min($prices),
            'catalogMaxPrice' => $prices === [] ? null : max($prices),
        ]);
    }

    /**
     * Facet checkbox options are always built from the *full* unfiltered
     * catalog, not the filtered result — so narrowing by Category never
     * makes a Family checkbox disappear, letting the customer broaden the
     * filter back out again.
     *
     * @param list<Product> $products
     * @param callable(Product): ?string $field
     * @return list<string>
     */
    private function distinctValues(array $products, callable $field): array
    {
        $values = [];
        foreach ($products as $product) {
            $value = $field($product);
            if ($value !== null) {
                $values[$value] = true;
            }
        }

        $distinct = array_keys($values);
        sort($distinct);
        return $distinct;
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
