<?php

declare(strict_types=1);

namespace App\Cart;

use Yiisoft\Session\SessionInterface;

/**
 * The cart lives entirely in session — no database row, no cart id, no
 * cross-device persistence. Deliberate per the design ("no local database
 * at all"): the checkout step is the only point this app's state ever
 * needs to survive, and that's handed straight to invoice as a
 * `POST /api/orders` call, not persisted here first.
 *
 * Product name/price are cached in the session row at add() time for
 * display, but are never trusted at checkout — `CheckoutController`
 * re-resolves both via `ProductCatalogClient` right before calling
 * invoice, the same "catalog is the only source of truth for price"
 * principle `OrderService` already enforces on the invoice side.
 */
final readonly class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(private SessionInterface $session)
    {
    }

    public function add(int $productId, string $name, float $price, float $quantity): void
    {
        if ($productId <= 0 || $quantity <= 0.0) {
            return;
        }

        $items = $this->readItems();
        $existing = $items[$productId] ?? null;
        $items[$productId] = [
            'name' => $name,
            'price' => $price,
            'quantity' => ($existing['quantity'] ?? 0.0) + $quantity,
        ];
        $this->writeItems($items);
    }

    public function updateQuantity(int $productId, float $quantity): void
    {
        $items = $this->readItems();
        if (!isset($items[$productId])) {
            return;
        }

        if ($quantity <= 0.0) {
            unset($items[$productId]);
        } else {
            $items[$productId]['quantity'] = $quantity;
        }
        $this->writeItems($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->readItems();
        unset($items[$productId]);
        $this->writeItems($items);
    }

    public function clear(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->readItems() === [];
    }

    /** @return list<CartItem> */
    public function getItems(): array
    {
        $items = [];
        foreach ($this->readItems() as $productId => $row) {
            $items[] = new CartItem($productId, $row['name'], $row['price'], $row['quantity']);
        }
        return $items;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->getItems() as $item) {
            $total += $item->subtotal();
        }
        return $total;
    }

    /** @return array<int, array{name: string, price: float, quantity: float}> */
    private function readItems(): array
    {
        /** @var mixed $raw */
        $raw = $this->session->get(self::SESSION_KEY);
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        /** @var mixed $row */
        foreach ($raw as $productId => $row) {
            if (is_array($row) && is_numeric($productId)) {
                /** @var mixed $name */
                $name = $row['name'] ?? null;
                $items[(int) $productId] = [
                    'name' => is_string($name) ? $name : '',
                    'price' => (float) ($row['price'] ?? 0),
                    'quantity' => (float) ($row['quantity'] ?? 0),
                ];
            }
        }
        return $items;
    }

    /** @param array<int, array{name: string, price: float, quantity: float}> $items */
    private function writeItems(array $items): void
    {
        $this->session->set(self::SESSION_KEY, $items);
    }
}
