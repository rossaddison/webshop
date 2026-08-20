<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds a Content-Security-Policy header to every response. The policy
 * string is injected via DI from config/web/di/content-security-policy.php.
 *
 * Directives summary:
 *   default-src 'self'                — block everything external by default
 *   script-src  'self'                — only our own bundled scripts
 *   style-src   'self' 'unsafe-inline' — Bootstrap 5 injects inline styles
 *   img-src     'self' data: blob:    — data: for e.g. embedded icons
 *   font-src    'self' data:          — embedded web-fonts
 *   connect-src 'self'                — AJAX stays on-origin
 *   form-action 'self'                — forms may not POST off-site
 *   frame-ancestors 'none'            — clickjacking prevention
 *   base-uri    'self'                — block <base> tag injection
 *   object-src  'none'                — no plugins (Flash etc.)
 */
final class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $policy) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        return $handler->handle($request)
            ->withHeader('Content-Security-Policy', $this->policy);
    }
}
