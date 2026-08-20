<?php

declare(strict_types=1);

namespace App\Checkout;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

/**
 * Calls invoice's `POST /api/orders` server-to-server — the customer's
 * own browser never talks to invoice directly until the redirect this
 * returns. See `App\Api\OrderService`/`OrdersController` on the invoice
 * side for the exact request/response shape this mirrors, and
 * `docs/STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md` for why the
 * response is a one-time login link rather than a bare url_key.
 */
final readonly class OrderApiClient
{
    public function __construct(
        private ClientInterface $httpClient,
        private string $baseUrl,
        private string $apiKey,
    ) {
    }

    /**
     * @param array{name: string, surname: string, email: string, address1?: string,
     *     address2?: string, city?: string, zip?: string, country?: string, phone?: string} $customer
     * @param list<array{product_id: int, quantity: float}> $items
     * @return string|null The absolute redirect_url to send the customer's
     *     browser to, or null if invoice rejected/failed the order.
     */
    public function createOrder(array $customer, array $items): ?string
    {
        $request = new Request(
            'POST',
            $this->baseUrl . '/en/api/orders',
            ['X-Api-Key' => $this->apiKey, 'Content-Type' => 'application/json'],
            json_encode(['customer' => $customer, 'items' => $items], JSON_THROW_ON_ERROR),
        );

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface) {
            return null;
        }

        if ($response->getStatusCode() !== 201) {
            return null;
        }

        /** @var mixed $decoded */
        $decoded = json_decode((string) $response->getBody(), true);
        /** @var mixed $redirectUrl */
        $redirectUrl = is_array($decoded) ? ($decoded['redirect_url'] ?? null) : null;

        return is_string($redirectUrl) && $redirectUrl !== '' ? $redirectUrl : null;
    }
}
