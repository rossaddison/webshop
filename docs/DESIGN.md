# Webshop — Design & Status (August 2026)

> Status: v1 built and confirmed live end-to-end against a real
> `invoice` instance with a real `INVOICE_API_KEY` — product images,
> real prices, and a real seeded category/subcategory/family taxonomy
> all round-tripping correctly through the gallery, filter sidebar, and
> checkout handoff. Psalm clean, 26/26 Testo tests. Three GitHub Actions
> workflows (build, static analysis, dependency check) all green on PHP
> 8.4 and 8.5. Not yet deployed to a real public host, and TypeScript
> cart interactivity still isn't built (see below).

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

## What's built (v1 scope, and beyond it)

- **`App\Catalog`**:
  - `ProductCatalogClient` calls `invoice`'s product API at the *bare*
    path (`/api/products`, not `/en/api/products`) — confirmed live that
    invoice's own Locale middleware 302-redirects the `en`
    (default-locale) prefix *away*, and `sendRequest()` doesn't follow
    redirects, so the `/en/`-prefixed form silently failed closed to an
    empty catalog. Caught by live testing, not the mocked-HTTP unit
    tests.
  - `Product` DTO carries `imageUrl` (joined from invoice's relative
    `image_path` + this app's own configured `INVOICE_API_BASE_URL`) and
    `family`/`category`/`subcategory`, both nullable.
  - `ProductFilter` — a pure value object over GET query params
    (`category[]`/`subcategory[]`/`family[]`/`min_price`/`max_price`);
    AND across groups, OR within one group, an active group always
    excludes uncategorized products. Facet checkbox options are always
    built from the *full* unfiltered catalog, so narrowing by one facet
    never makes another facet's option disappear.
  - `ProductsController` — the listing page renders as a
    `Yiisoft\Bootstrap5\Carousel` gallery (three products per slide,
    each an independently-clickable tile with its own photo or a
    placeholder icon), matching invoice's own `resources/views/site/gallery.php`
    precedent rather than inventing a card-grid layout. A left sidebar
    holds the filter checkboxes/price-range form; the detail page shows
    the full-size photo.
- **`App\Cart`** — `CartService` (session array, no DB row),
  `CartController` (add/update/remove, all re-resolving price from the
  catalog rather than trusting the form — same principle `invoice`'s own
  `OrderService` already enforces for the order itself).
- **`App\Checkout`** — `CheckoutForm`, `OrderApiClient` (also fixed to
  the bare `/api/orders` path, same Locale-middleware issue as the
  catalog client above), `CheckoutController`. Fields use placeholder
  text instead of visible `<label>`s; Bootstrap's own `form-control`/
  `form-label` classes and HTML5 validation attributes (`required`,
  `maxlength`) come from `yiisoft/form`'s theme, which needed its own
  fix — `defaultTheme` has to be a sibling key of `themes` in
  `common/params.php`, not nested inside it (`ThemeContainer::initialize()`
  takes them as two separate arguments).
- **Bootstrap 5, served locally, not from a CDN** — published via
  `Yiisoft\Assets\AssetManager` + `Yiisoft\Bootstrap5\Assets\BootstrapAsset`
  from `node_modules/bootstrap/dist` (hence `npm install` being part of
  setup now). The `customizedBundles` config block copied from
  `ddd-template` (which needs it, for its own separately-compiled
  stylesheet) was silently zeroing out `BootstrapAsset`'s CSS array here
  — removed entirely, since nothing else here supplies Bootstrap CSS.
- **Branding** — inline SVG shopping-bag logo + site name in the navbar,
  reduced root `font-size` (Bootstrap sizes most things in `rem`, so this
  scales the whole page).
- **CI** — three GitHub Actions workflows (`ci.yml` build/test,
  `static-analysis.yml` Psalm, `dependency.yml` composer-require-checker),
  PHP 8.4 + 8.5, right-sized for a young/small repo rather than a direct
  copy of `invoice`'s much larger CI suite. No CodeQL yet — it hard-fails
  rather than no-ops on a repo with zero first-party JS/TS source, and
  this repo has none yet.
- Config/DI scaffolding trimmed hard from `ddd-template`: **no Cycle ORM,
  no database, no Auth/User/RBAC modules** — none of that applies here.
  Kept: router, session, CSRF, translator, view renderer, Bootstrap 5,
  rate limiter, the same esbuild-free server-rendered form approach (no
  TypeScript cart interactivity yet — see below).

## Explicitly not built yet

- **TypeScript cart interactivity** (planned: minimal add/remove/update
  quantity, esbuild → IIFE, matching `invoice`'s own build convention).
  The cart fully works via plain HTML form POSTs today — this was scoped
  as progressive enhancement, not a correctness requirement, and there's
  no esbuild pipeline wired up here yet at all (also why the price-range
  filter is a plain min/max input pair rather than a real drag-slider).
  `resources/views/cart/index.php`'s quantity `<form>`s are the thing to
  wire up.
- **Full-text product search** — category/subcategory/family/price
  filtering is built (see above); a search box isn't.
- **CodeQL** — see the CI note above.
- An account system of its own — still an explicit non-goal; the
  one-time-login handoff (see "The handoff" above) exists specifically
  so this app never needs one.

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
