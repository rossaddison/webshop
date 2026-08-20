<?php

declare(strict_types=1);

namespace Tests\Testo\Catalog;

use App\Catalog\ProductCatalogClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ProductCatalogClient — calls invoice's `GET /api/products`
 * (+ `/{id}`) directly at the `/en/` path (see the class's own docblock
 * for why the language segment is never left to Locale-middleware
 * redirect), fails closed to an empty list / null on any transport or
 * shape problem.
 */
#[Test]
final class ProductCatalogClientTest
{
    public function listProductsMapsTheApiResponse(): void
    {
        $body = json_encode([
            ['id' => 1, 'sku' => 'W-1', 'name' => 'Widget', 'description' => 'A widget', 'price' => 9.99, 'unit' => 'each', 'image_path' => '/products/widget.jpg'],
            ['id' => 2, 'sku' => null, 'name' => 'Gadget', 'description' => null, 'price' => 4.5, 'unit' => null, 'image_path' => null],
        ], JSON_THROW_ON_ERROR);

        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()->with(m::on(
            static fn (Request $r): bool => (string) $r->getUri() === 'https://invoice.test/api/products'
                && $r->getHeaderLine('X-Api-Key') === 'secret-key',
        ))->andReturn(new Response(200, [], $body));

        $client = new ProductCatalogClient($httpClient, 'https://invoice.test', 'secret-key');
        $products = $client->listProducts();

        Assert::count($products, 2);
        Assert::same(1, $products[0]->id);
        Assert::same('Widget', $products[0]->name);
        Assert::same(9.99, $products[0]->price);
        Assert::same('https://invoice.test/products/widget.jpg', $products[0]->imageUrl);
        Assert::same('Gadget', $products[1]->displayName());
        Assert::null($products[1]->imageUrl);
    }

    public function listProductsReturnsEmptyOnANon200Response(): void
    {
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()->andReturn(new Response(401, [], 'Invalid API key.'));

        $client = new ProductCatalogClient($httpClient, 'https://invoice.test', 'wrong-key');

        Assert::count($client->listProducts(), 0);
    }

    public function listProductsReturnsEmptyOnATransportFailure(): void
    {
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()->andThrow(
            new class () extends \RuntimeException implements ClientExceptionInterface {},
        );

        $client = new ProductCatalogClient($httpClient, 'https://invoice.test', 'secret-key');

        Assert::count($client->listProducts(), 0);
    }

    public function getProductReturnsNullForANonPositiveId(): void
    {
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldNotReceive('sendRequest');

        $client = new ProductCatalogClient($httpClient, 'https://invoice.test', 'secret-key');

        Assert::null($client->getProduct(0));
    }

    public function getProductMapsASingleProduct(): void
    {
        $body = json_encode(
            ['id' => 7, 'sku' => 'W-7', 'name' => 'Widget 7', 'description' => 'Seventh widget', 'price' => 19.5, 'unit' => 'each'],
            JSON_THROW_ON_ERROR,
        );

        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()->with(m::on(
            static fn (Request $r): bool => (string) $r->getUri() === 'https://invoice.test/api/products/7',
        ))->andReturn(new Response(200, [], $body));

        $client = new ProductCatalogClient($httpClient, 'https://invoice.test', 'secret-key');
        $product = $client->getProduct(7);

        Assert::notNull($product);
        Assert::same('Widget 7', $product->name);
    }
}
