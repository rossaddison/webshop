<?php

declare(strict_types=1);

use App\Catalog\ProductCatalogClient;
use App\Checkout\OrderApiClient;
use Psr\Http\Client\ClientInterface;
use Yiisoft\Definitions\Reference;

/**
 * @var array $params
 * @var array $params['invoice']
 * @var string $params['invoice']['apiBaseUrl']
 * @var string $params['invoice']['apiKey']
 */

return [
    ProductCatalogClient::class => [
        '__construct()' => [
            Reference::to(ClientInterface::class),
            $params['invoice']['apiBaseUrl'],
            $params['invoice']['apiKey'],
        ],
    ],
    OrderApiClient::class => [
        '__construct()' => [
            Reference::to(ClientInterface::class),
            $params['invoice']['apiBaseUrl'],
            $params['invoice']['apiKey'],
        ],
    ],
];
