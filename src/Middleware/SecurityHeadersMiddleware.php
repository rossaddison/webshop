<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds baseline security headers to every response, guaranteeing they're
 * present regardless of the web server in front of the app (rather than
 * relying on e.g. Apache .htaccess, which a different server or proxy could
 * silently drop).
 *
 * @psalm-type HeaderMap = array<string, string>
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @param HeaderMap $headers
     */
    public function __construct(private readonly array $headers) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);
        foreach ($this->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }
}
