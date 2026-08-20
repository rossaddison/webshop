<?php

declare(strict_types=1);

use App\Cart\CartService;
use Psr\Log\LogLevel;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Form\Field\SubmitButton;
use Yiisoft\Form\Field\ErrorSummary;
use Yiisoft\FormModel\ValidationRulesEnricher;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

$buttonClass = 'buttonClass()';
$containerClass = 'containerClass()';

return [
    'yiisoft/log-target-file' => [
        'fileTarget' => [
            'file' => '@runtime/logs/app.log',
            'levels' => [
                LogLevel::EMERGENCY,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::INFO,
                LogLevel::DEBUG,
            ],
            'dirMode' => 0o755,
            'fileMode' => null,
        ],
        'fileRotator' => [
            'maxFileSize' => 500,
            'maxFiles' => 100,
            'fileMode' => null,
            'compressRotatedFiles' => false,
        ],
    ],

    'env' => $_ENV['YII_ENV'] ?? 'dev',

    'product' => [
        'name'    => 'Webshop',
        'version' => 'pre-release',
        'server'  => PHP_VERSION,
    ],

    // The invoice app this storefront proxies product/order calls to —
    // no local database of its own. See App\Catalog\ProductCatalogClient
    // and App\Checkout\OrderApiClient.
    'invoice' => [
        'apiBaseUrl' => rtrim($_ENV['INVOICE_API_BASE_URL'] ?? '', '/'),
        'apiKey' => $_ENV['INVOICE_API_KEY'] ?? '',
    ],

    'yiisoft/aliases' => [
        'aliases' => [
            '@root' => dirname(__DIR__, 2),
            '@views' => dirname(__DIR__, 2) . '/resources/views',
            '@assets' => '@root/public/assets',
            '@assetsUrl' => '@baseUrl/assets',
            '@baseUrl' => '',
            '@messages' => '@resources/messages',
            '@npm' => '@root/node_modules',
            '@public' => '@root/public',
            '@resources' => '@root/resources',
            '@runtime' => '@root/runtime',
            '@src' => '@root/src',
            '@validatorMessages' => '@vendor/yiisoft/validator/messages',
            '@vendor' => '@root/vendor',
            '@layout' => '@views/layout',
        ],
    ],
    'yiisoft/form' => [
        'themes' => [
            'defaultTheme' => 'bootstrap5-vertical',
            'validationRulesEnricher' => new ValidationRulesEnricher(),
            'bootstrap5-vertical' => [
                'template' => "{label}\n{input}\n{hint}\n{error}",
                'containerClass' => 'mb-3',
                'labelClass' => 'form-label',
                'inputClass' => 'form-control',
                'hintClass' => 'form-text',
                'errorClass' => 'invalid-feedback',
                'inputValidClass' => 'is-valid',
                'inputInvalidClass' => 'is-invalid',
                'fieldConfigs' => [
                    ErrorSummary::class => [
                        $containerClass => ['alert alert-danger'],
                        'listAttributes()' => [['class' => 'mb-0']],
                        'header()' => [''],
                    ],
                    SubmitButton::class => [
                        $buttonClass => ['btn btn-primary'],
                    ],
                ],
                'enrichFromValidationRules' => true,
            ],
        ],
    ],
    'yiisoft/router-fastroute' => [
        'enableCache' => false,
        'encodeRaw' => true,
    ],
    'yiisoft/translator' => [
        'locale' => 'en',
        'fallbackLocale' => 'en',
        'defaultCategory' => 'app',
        'validatorCategory' => 'yii-validator',
    ],
    'yiisoft/view' => [
        'basePath' => '@views',
        'parameters' => [
            'assetManager' => Reference::to(AssetManager::class),
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
            'translator' => Reference::to(TranslatorInterface::class),
            'session' => Reference::to(SessionInterface::class),
            'cart' => Reference::to(CartService::class),
            'flash' => Reference::to(Flash::class),
        ],
    ],
    'yiisoft/cookies' => [
        // Must be set per deployment via COOKIE_SECRET_KEY.
        'secretKey' => $_ENV['COOKIE_SECRET_KEY'] ?? '',
    ],
    'yiisoft/yii-view-renderer' => [
        'viewPath' => '@views',
        'layout' => '@views/layout/main.php',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],
];
