<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Checkbox/range-driven catalog filtering — category / subcategory /
 * family (the same three-level taxonomy `invoice`'s `GET /api/products`
 * feed exposes, see `App\Api\ProductsController::toArray()` on the
 * invoice side) plus a min/max price range. Pure GET query params
 * (`category[]`/`subcategory[]`/`family[]`/`min_price`/`max_price`), no
 * JavaScript needed: the sidebar form just submits itself. Price is a
 * pair of plain number inputs rather than a drag-slider — this app has
 * no JS/TS build step yet (see package.json), and a real dual-handle
 * slider needs one; a min/max pair reaches the same outcome with zero
 * new infrastructure.
 *
 * A checkbox group passes a product only if that group has no boxes
 * checked at all (nothing selected = no restriction) or the product's
 * own value for that field is one of the checked ones. Uncategorized
 * products (a `null` field) always pass an inactive group but never pass
 * an active one — checking any Category box hides products with no
 * category, which is the expected "narrow the list" behaviour.
 */
final readonly class ProductFilter
{
    /**
     * @param list<string> $categories
     * @param list<string> $subcategories
     * @param list<string> $families
     */
    public function __construct(
        public array $categories = [],
        public array $subcategories = [],
        public array $families = [],
        public ?float $minPrice = null,
        public ?float $maxPrice = null,
    ) {
    }

    /**
     * @param array<array-key, mixed> $queryParams
     */
    public static function fromQueryParams(array $queryParams): self
    {
        return new self(
            categories: self::stringList($queryParams['category'] ?? null),
            subcategories: self::stringList($queryParams['subcategory'] ?? null),
            families: self::stringList($queryParams['family'] ?? null),
            minPrice: self::nonNegativeFloat($queryParams['min_price'] ?? null),
            maxPrice: self::nonNegativeFloat($queryParams['max_price'] ?? null),
        );
    }

    /**
     * @param list<Product> $products
     * @return list<Product>
     */
    public function apply(array $products): array
    {
        return array_values(array_filter(
            $products,
            fn (Product $product): bool => $this->matches($this->categories, $product->category)
                && $this->matches($this->subcategories, $product->subcategory)
                && $this->matches($this->families, $product->family)
                && ($this->minPrice === null || $product->price >= $this->minPrice)
                && ($this->maxPrice === null || $product->price <= $this->maxPrice),
        ));
    }

    public function isEmpty(): bool
    {
        return $this->categories === []
            && $this->subcategories === []
            && $this->families === []
            && $this->minPrice === null
            && $this->maxPrice === null;
    }

    /** @param list<string> $selected */
    private function matches(array $selected, ?string $value): bool
    {
        return $selected === [] || ($value !== null && in_array($value, $selected, true));
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $strings = [];
        /** @var mixed $item */
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }
        return $strings;
    }

    /**
     * A blank/missing/non-numeric/negative submission is treated the same
     * as "no bound set" rather than as a validation error — a price
     * filter degrading to "no restriction" is harmless, unlike silently
     * dropping a category the customer actually meant to check.
     */
    private static function nonNegativeFloat(mixed $value): ?float
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return null;
        }
        if (is_string($value) && ($value === '' || !is_numeric($value))) {
            return null;
        }

        $float = (float) $value;
        return $float >= 0.0 ? $float : null;
    }
}
