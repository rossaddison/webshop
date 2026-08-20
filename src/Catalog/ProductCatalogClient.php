<?php

declare(strict_types=1);

namespace App\Catalog;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

/**
 * Calls invoice's `GET /api/products` (+ `/{id}`) — this app has no
 * product data of its own. Fails closed (empty list / null) on any
 * transport or shape problem rather than throwing, since a catalog
 * hiccup should degrade the storefront to "no products shown", not a
 * crashed page.
 *
 * `X-Api-Key` matches `App\Middleware\ApiKeyAuthMiddleware` on the
 * invoice side exactly. The path is deliberately bare (`/api/products`,
 * not `/en/api/products`) — confirmed live: invoice's own Locale
 * middleware 302-redirects the `en` (default-locale) prefix *away*,
 * same behaviour this app's own Locale middleware has for its own
 * routes. `sendRequest()` doesn't follow redirects, so requesting the
 * `/en/` form here would always come back as a 302 and fail closed to
 * an empty catalog — caught by live-testing against a real invoice
 * instance, not something the mocked-HTTP unit tests could have shown.
 */
final readonly class ProductCatalogClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private string $baseUrl,
        private string $apiKey,
    ) {
    }

    /** @return list<Product> */
    public function listProducts(): array
    {
        $decoded = $this->getJson('/api/products');
        if (!is_array($decoded)) {
            return [];
        }

        $products = [];
        /** @var mixed $row */
        foreach ($decoded as $row) {
            if (is_array($row)) {
                $products[] = Product::fromApiResponse($row);
            }
        }
        return $products;
    }

    public function getProduct(int $id): ?Product
    {
        if ($id <= 0) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = $this->getJson('/api/products/' . $id);
        return is_array($decoded) ? Product::fromApiResponse($decoded) : null;
    }

    private function getJson(string $path): mixed
    {
        try {
            $response = $this->httpClient->sendRequest(
                new Request('GET', $this->baseUrl . $path, ['X-Api-Key' => $this->apiKey]),
            );
        } catch (ClientExceptionInterface) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode((string) $response->getBody(), true);
        return $decoded;
    }
}
