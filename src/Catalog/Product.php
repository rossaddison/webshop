<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * A read-only projection of one row from invoice's `GET /api/products`
 * feed (`App\Api\ProductsController::toArray()` on the invoice side) —
 * this app never stores or writes product data itself.
 */
final readonly class Product
{
    public function __construct(
        public int $id,
        public ?string $sku,
        public ?string $name,
        public ?string $description,
        public float $price,
        public ?string $unit,
        public ?string $imageUrl,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     * @param string $invoiceBaseUrl Joined with the API's `image_path` (a
     *     relative path, not an absolute URL — see
     *     `App\Api\ProductsController::firstImagePath()`'s own docblock on
     *     the invoice side for why) to build a full `<img src>` this app
     *     can use directly.
     */
    public static function fromApiResponse(array $data, string $invoiceBaseUrl): self
    {
        /**
         * @var mixed $sku
         * @var mixed $name
         * @var mixed $description
         * @var mixed $unit
         * @var mixed $imagePath
         */
        [$sku, $name, $description, $unit, $imagePath] = [
            $data['sku'] ?? null, $data['name'] ?? null, $data['description'] ?? null,
            $data['unit'] ?? null, $data['image_path'] ?? null,
        ];

        return new self(
            id: (int) ($data['id'] ?? 0),
            sku: is_string($sku) ? $sku : null,
            name: is_string($name) ? $name : null,
            description: is_string($description) ? $description : null,
            price: (float) ($data['price'] ?? 0),
            unit: is_string($unit) ? $unit : null,
            imageUrl: is_string($imagePath) && $imagePath !== '' ? $invoiceBaseUrl . $imagePath : null,
        );
    }

    public function displayName(): string
    {
        return $this->name ?? ('Product #' . $this->id);
    }
}
