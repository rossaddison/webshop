# Webshop — Design & Status (August 2026)

> Status: v1 built and verified — full DI boot, real HTTP smoke test
> (`/`, `/products/{id}`, `/cart`, `/checkout` all confirmed live against
> a local dev server), Psalm clean, 15/15 Testo tests. Not yet deployed
> anywhere or tested against a real `invoice` instance with a real API
> key.

## Why this repo exists

`rossaddison/invoice` already integrates 17 payment gateways and a full
guest invoice-payment page — everything a storefront's checkout needs.
Rather than build a storefront's own payment code, this app is a thin
front door: it owns the product listing and cart, then hands off to
`invoice`'s own existing checkout for payment.

## Two repos, clear responsibilities

- **`invoice`** stays the system of record. It exposes a small API-key
  authenticated surface this app calls: `GET /api/products` (+ detail),
  `POST /api/orders`. See `invoice`'s own
  `docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md` and
  `docs/STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md`.
- **`webshop`** (this repo) — the storefront. **Deliberately no local
  database at all.** Product data is fetched live via `invoice`'s API;
  the cart lives in session (`App\Cart\CartService`). One source of
  truth, zero duplicated state.

## The handoff

1. Customer builds a cart (session-only, `App\Cart\CartService`).
2. Checkout form (`App\Checkout\CheckoutForm`) collects name/surname/
   email/address.
3. `App\Checkout\CheckoutController::submit()` calls `invoice`'s
   `POST /api/orders` server-to-server via `OrderApiClient`.
4. `invoice` creates the order, auto-creates (or reuses) a login account
   for the customer, and returns a one-time login link — **not** a bare
   invoice URL, because a fresh customer has no `invoice` session to view
   one with. See `invoice`'s own
   `STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md` for why —
   discovering this gap was most of the work building this repo actually
   depended on.
5. This app 302s the customer's own browser to that link. It lands them
   already logged in on `invoice`, at their invoice's own view/pay page —
   the same page every other invoice recipient already uses, all 17
   gateways included, zero new payment code written here.

## What's built (v1 scope)

- **`App\Catalog`** — `ProductCatalogClient` (calls `invoice`'s product
  API directly at `/en/api/...`, not relying on a Locale-middleware
  redirect — that's bitten `invoice` before), `Product` DTO,
  `ProductsController` (list + detail pages).
- **`App\Cart`** — `CartService` (session array, no DB row),
  `CartController` (add/update/remove, all re-resolving price from the
  catalog rather than trusting the form — same principle `invoice`'s own
  `OrderService` already enforces for the order itself).
- **`App\Checkout`** — `CheckoutForm`, `OrderApiClient`, `CheckoutController`.
- Config/DI scaffolding trimmed hard from `ddd-template`: **no Cycle ORM,
  no database, no Auth/User/RBAC modules** — none of that applies here.
  Kept: router, session, CSRF, translator, view renderer, Bootstrap 5,
  rate limiter, the same esbuild-free server-rendered form approach (no
  TypeScript cart interactivity yet — see below).

## Explicitly not built yet

- **TypeScript cart interactivity** (planned: minimal add/remove/update
  quantity, esbuild → IIFE, matching `invoice`'s own build convention).
  The cart fully works via plain HTML form POSTs today — this was scoped
  as progressive enhancement, not a correctness requirement, and time
  ran out before it was built. `resources/views/cart/index.php`'s
  quantity `<form>`s are the thing to wire up.
- Search/categories/filtering, an account system of its own — explicit
  v1 non-goals per the original design.
- Real end-to-end verification against a live `invoice` deployment with
  a real `INVOICE_API_KEY` (`yii api-client/generate` on the `invoice`
  side) — only verified so far against a local dev server with an empty/
  invalid key, confirming the app fails closed correctly (empty catalog,
  404s) rather than crashing.

## Stack decision trail

Scaffolded from `ddd-template` (`c:\wamp64\www\ddd-template`) rather than
built from scratch — same PHP 8.3-8.5/Yii3 stack, same DI/routing/config
conventions already proven there. Trimmed composer.json from ~70
packages to ~45: dropped everything Cycle/DB/Auth/RBAC/Telegram/mailer-
related, kept the generic web-app shell (router, session, CSRF, forms,
translator, view renderer, rate limiter, PSR-17/18 via `nyholm/psr7` +
Guzzle instead of `ddd-template`'s `httpsoft/http-message`, since Guzzle
was needed anyway for the `invoice` API calls and adding a second PSR-17
implementation for no reason wasn't worth it).

`CookieMiddleware` (present in `ddd-template`'s own middleware pipeline)
was dropped entirely rather than re-wired — its only real use there was
signing the "remember me" login cookie, via a `yiisoft/user`-specific
`CookieLogin` dependency this app doesn't have. This app has no cookies
of its own to sign, so there was nothing to wire a replacement for.
