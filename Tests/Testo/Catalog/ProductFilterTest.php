<?php

declare(strict_types=1);

namespace Tests\Testo\Catalog;

use App\Catalog\Product;
use App\Catalog\ProductFilter;
use Testo\Assert;
use Testo\Test;

/**
 * Covers ProductFilter — the checkbox-driven category/subcategory/family
 * narrowing behind the catalog gallery's filter sidebar. See the class's
 * own docblock for the "no boxes checked in a group = no restriction from
 * that group" / "an active group always excludes uncategorized products"
 * rules being verified here.
 */
#[Test]
final class ProductFilterTest
{
    private function product(
        int $id,
        ?string $family = null,
        ?string $category = null,
        ?string $subcategory = null,
        float $price = 9.99,
    ): Product {
        return new Product(
            id: $id,
            sku: null,
            name: 'Product ' . $id,
            description: null,
            price: $price,
            unit: null,
            imageUrl: null,
            family: $family,
            category: $category,
            subcategory: $subcategory,
        );
    }

    public function fromQueryParamsReadsCheckedArraysAndIgnoresJunk(): void
    {
        $filter = ProductFilter::fromQueryParams([
            'category' => ['Computing', '', 5],
            'subcategory' => 'not-an-array',
            // 'family' key absent entirely
        ]);

        Assert::same(['Computing'], $filter->categories);
        Assert::same([], $filter->subcategories);
        Assert::same([], $filter->families);
    }

    public function fromQueryParamsReadsAValidPriceRange(): void
    {
        $filter = ProductFilter::fromQueryParams(['min_price' => '10.50', 'max_price' => '99']);

        Assert::same(10.50, $filter->minPrice);
        Assert::same(99.0, $filter->maxPrice);
    }

    public function fromQueryParamsTreatsBlankOrJunkPriceAsNoBound(): void
    {
        $filter = ProductFilter::fromQueryParams([
            'min_price' => '',
            'max_price' => 'not-a-number',
        ]);

        Assert::null($filter->minPrice);
        Assert::null($filter->maxPrice);
    }

    public function fromQueryParamsTreatsANegativePriceAsNoBound(): void
    {
        $filter = ProductFilter::fromQueryParams(['min_price' => '-5']);

        Assert::null($filter->minPrice);
    }

    public function emptyFilterPassesEverythingIncludingUncategorized(): void
    {
        $filter = new ProductFilter();
        $products = [
            $this->product(1, category: 'Computing'),
            $this->product(2),
        ];

        Assert::same($products, $filter->apply($products));
        Assert::true($filter->isEmpty());
    }

    public function categoryFilterExcludesUncategorizedAndNonMatchingProducts(): void
    {
        $filter = new ProductFilter(categories: ['Computing']);
        $matching = $this->product(1, category: 'Computing');
        $products = [
            $matching,
            $this->product(2, category: 'Accessories'),
            $this->product(3),
        ];

        Assert::same([$matching], $filter->apply($products));
        Assert::false($filter->isEmpty());
    }

    public function multipleGroupsCombineWithAnd(): void
    {
        $filter = new ProductFilter(categories: ['Computing'], families: ['Input Devices']);
        $matching = $this->product(1, family: 'Input Devices', category: 'Computing');
        $products = [
            $matching,
            // Right category, wrong family — excluded.
            $this->product(2, family: 'Monitors', category: 'Computing'),
            // Right family, wrong category — excluded.
            $this->product(3, family: 'Input Devices', category: 'Accessories'),
        ];

        Assert::same([$matching], $filter->apply($products));
    }

    public function oneGroupAcceptsAnyOfSeveralCheckedValues(): void
    {
        $filter = new ProductFilter(categories: ['Computing', 'Accessories']);
        $computing = $this->product(1, category: 'Computing');
        $accessories = $this->product(2, category: 'Accessories');
        $products = [$computing, $accessories, $this->product(3, category: 'Garden')];

        Assert::same([$computing, $accessories], $filter->apply($products));
    }

    public function priceRangeIsInclusiveAtBothEnds(): void
    {
        $filter = new ProductFilter(minPrice: 20.00, maxPrice: 50.00);
        $atMin = $this->product(1, price: 20.00);
        $atMax = $this->product(2, price: 50.00);
        $products = [
            $atMin,
            $atMax,
            $this->product(3, price: 19.99),
            $this->product(4, price: 50.01),
        ];

        Assert::same([$atMin, $atMax], $filter->apply($products));
        Assert::false($filter->isEmpty());
    }

    public function priceRangeCombinesWithCategoryFilterAsAnd(): void
    {
        $filter = new ProductFilter(categories: ['Computing'], minPrice: 100.00);
        $matching = $this->product(1, category: 'Computing', price: 189.00);
        $products = [
            $matching,
            // Right category, too cheap — excluded.
            $this->product(2, category: 'Computing', price: 19.99),
            // Expensive enough, wrong category — excluded.
            $this->product(3, category: 'Accessories', price: 200.00),
        ];

        Assert::same([$matching], $filter->apply($products));
    }

    public function onlyAMinPriceLeavesTheUpperEndOpen(): void
    {
        $filter = new ProductFilter(minPrice: 50.00);
        $products = [
            $this->product(1, price: 49.99),
            $this->product(2, price: 50.00),
            $this->product(3, price: 999.00),
        ];

        Assert::same([$products[1], $products[2]], $filter->apply($products));
    }
}
