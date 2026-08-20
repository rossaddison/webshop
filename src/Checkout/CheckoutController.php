<?php

declare(strict_types=1);

namespace App\Checkout;

use App\Cart\CartService;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CheckoutController
{
    private WebViewRenderer $webViewRenderer;

    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly CartService $cartService,
        private readonly TranslatorInterface $translator,
        private readonly Flash $flash,
    ) {
        $this->webViewRenderer = $webViewRenderer->withControllerName('checkout');
    }

    public function index(CheckoutForm $form): ResponseInterface
    {
        if ($this->cartService->isEmpty()) {
            return $this->webService->getRedirectResponse('cart/index');
        }

        return $this->webViewRenderer->render('index', [
            'form' => $form,
            'items' => $this->cartService->getItems(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    public function submit(
        ServerRequestInterface $request,
        CheckoutForm $form,
        FormHydrator $formHydrator,
        OrderApiClient $orderApiClient,
    ): ResponseInterface {
        if ($this->cartService->isEmpty()) {
            return $this->webService->getRedirectResponse('cart/index');
        }

        if (!$formHydrator->populateFromPostAndValidate($form, $request)) {
            return $this->webViewRenderer->render('index', [
                'form' => $form,
                'items' => $this->cartService->getItems(),
                'total' => $this->cartService->getTotal(),
            ]);
        }

        $items = [];
        foreach ($this->cartService->getItems() as $item) {
            $items[] = ['product_id' => $item->productId, 'quantity' => $item->quantity];
        }

        $redirectUrl = $orderApiClient->createOrder($form->toCustomerArray(), $items);
        if ($redirectUrl === null) {
            $this->flash->add('danger', $this->translator->translate('checkout.failed'), true);
            return $this->webService->getRedirectResponse('checkout/index');
        }

        $this->cartService->clear();

        // An absolute, external URL on invoice's own domain — not a named
        // route of this app, so WebControllerService::getRedirectResponse()
        // (which only ever generates internal routes) doesn't apply here.
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader(Header::LOCATION, $redirectUrl);
    }
}
