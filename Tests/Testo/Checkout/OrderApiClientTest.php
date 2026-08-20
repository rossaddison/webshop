<?php

declare(strict_types=1);

namespace Tests\Testo\Checkout;

use App\Checkout\OrderApiClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Testo\Assert;
use Testo\Test;

/**
 * Covers OrderApiClient — the server-to-server call to invoice's
 * `POST /api/orders`. The customer's own browser never touches invoice
 * until the `redirect_url` this returns; see the class's own docblock
 * and docs/STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md on the
 * invoice side for why that's a one-time login link, not a bare url_key.
 */
#[Test]
final class OrderApiClientTest
{
    /** @return array{name: string, surname: string, email: string} */
    private function customer(): array
    {
        return ['name' => 'Ada', 'surname' => 'Lovelace', 'email' => 'ada@example.test'];
    }

    /** @return list<array{product_id: int, quantity: float}> */
    private function items(): array
    {
        return [['product_id' => 1, 'quantity' => 2.0]];
    }

    public function returnsTheRedirectUrlOnSuccess(): void
    {
        $body = json_encode(['redirect_url' => 'https://invoice.test/webshop/orderLogin/masked'], JSON_THROW_ON_ERROR);

        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()->with(m::on(
            static function (Request $r): bool {
                if ((string) $r->getUri() !== 'https://invoice.test/api/orders') {
                    return false;
                }
                if ($r->getHeaderLine('X-Api-Key') !== 'secret-key') {
                    return false;
                }
                /** @var array{customer: array{email: string}, items: list<array{product_id: int}>} $decoded */
                $decoded = json_decode((string) $r->getBody(), true, 512, JSON_THROW_ON_ERROR);
                return $decoded['customer']['email'] === 'ada@example.test'
                    && $decoded['items'][0]['product_id'] === 1;
            },
        ))->andReturn(new Response(201, [], $body));

        $client = new OrderApiClient($httpClient, 'https://invoice.test', 'secret-key');
        $redirectUrl = $client->createOrder($this->customer(), $this->items());

        Assert::same('https://invoice.test/webshop/orderLogin/masked', $redirectUrl);
    }

    public function returnsNullOnANon201Response(): void
    {
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()
            ->andReturn(new Response(422, [], 'Could not create order'));

        $client = new OrderApiClient($httpClient, 'https://invoice.test', 'secret-key');

        Assert::null($client->createOrder($this->customer(), $this->items()));
    }

    public function returnsNullOnATransportFailure(): void
    {
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()->andThrow(
            new class () extends \RuntimeException implements ClientExceptionInterface {},
        );

        $client = new OrderApiClient($httpClient, 'https://invoice.test', 'secret-key');

        Assert::null($client->createOrder($this->customer(), $this->items()));
    }

    public function returnsNullWhenTheResponseHasNoRedirectUrl(): void
    {
        /** @var ClientInterface&m\MockInterface $httpClient */
        $httpClient = m::mock(ClientInterface::class);
        $httpClient->shouldReceive('sendRequest')->once()
            ->andReturn(new Response(201, [], json_encode([], JSON_THROW_ON_ERROR)));

        $client = new OrderApiClient($httpClient, 'https://invoice.test', 'secret-key');

        Assert::null($client->createOrder($this->customer(), $this->items()));
    }
}
