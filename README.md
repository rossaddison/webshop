# webshop

[![webshop build](https://github.com/rossaddison/webshop/actions/workflows/ci.yml/badge.svg)](https://github.com/rossaddison/webshop/actions/workflows/ci.yml)
[![webshop static analysis](https://github.com/rossaddison/webshop/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/rossaddison/webshop/actions/workflows/static-analysis.yml)
[![webshop dependency checker](https://github.com/rossaddison/webshop/actions/workflows/dependency.yml/badge.svg)](https://github.com/rossaddison/webshop/actions/workflows/dependency.yml)

A headless storefront for [rossaddison/invoice](https://github.com/rossaddison/invoice) — no
local database of its own. Product catalog and checkout are proxied
live to `invoice`'s API; the cart lives in session. See
[`docs/DESIGN.md`](docs/DESIGN.md) for the full design (note: written
before most of what's below, some details there — the `/en/api/...`
path in particular — are now out of date).

## Setup

```
composer install
npm install
cp .env.example .env
```

`npm install` matters, not just `composer install` — Bootstrap 5 is
published locally from `node_modules` (via `Yiisoft\Bootstrap5\Assets\BootstrapAsset`,
registered in the main layout), not loaded from a CDN, so every page
renders unstyled without it.

Fill in `.env`:

- `COOKIE_SECRET_KEY` — any random string.
- `INVOICE_API_BASE_URL` — the `invoice` deployment this storefront talks to.
- `INVOICE_API_KEY` — minted on the `invoice` side via `yii api-client/generate`.

```
php yii serve
```

## What's here

- **Product gallery** — a `Bootstrap5\Carousel` (three products per
  slide), each product an independently-clickable tile with its own
  photo (proxied from `invoice`'s `image_path`) or a placeholder icon.
- **Filter sidebar** — checkbox filters for Category / Subcategory /
  Family (`invoice`'s `Product` taxonomy) plus a min/max price range.
  Pure GET query params — the form just submits itself, no JavaScript;
  this repo has no JS/TS build step yet, so a real dual-handle slider
  was skipped for a plain number-input pair instead.
- **Checkout** — name/surname/email/address, POSTs to `invoice`'s
  `POST /api/orders` server-to-server, then redirects the customer's own
  browser to the one-time-login link the response contains, landing them
  already logged in on `invoice` at their invoice's own pay page (all 17
  of `invoice`'s payment gateways, zero payment code written here).
- **Branding** — inline SVG logo + site name in the navbar, reduced base
  font size, placeholder-text form fields instead of visible labels.

## Tests

```
vendor/bin/testo --suite=Unit
vendor/bin/psalm --no-cache
```

## CI

Three GitHub Actions workflows, each on push/PR to `master` plus a
schedule:

- `ci.yml` ("webshop build") — Testo unit suite, PHP 8.4 + 8.5 on
  Ubuntu + Windows.
- `static-analysis.yml` — Psalm, PHP 8.4 + 8.5 on Ubuntu.
- `dependency.yml` — `composer-require-checker` (every symbol used has
  a real declared dependency), PHP 8.4 + 8.5 on Ubuntu.

No CodeQL yet — this repo has no first-party JavaScript/TypeScript
source for it to scan (CodeQL doesn't support PHP), and it hard-fails
rather than no-ops on a language with zero source files. Worth adding
once real TypeScript exists here.
