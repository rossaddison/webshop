# webshop

A headless storefront for [rossaddison/invoice](https://github.com/rossaddison/invoice) — no
local database of its own. Product catalog and checkout are proxied
live to `invoice`'s API; the cart lives in session. See
[`docs/DESIGN.md`](docs/DESIGN.md) for the full design and current status.

## Setup

```
composer install
cp .env.example .env
```

Fill in `.env`:

- `COOKIE_SECRET_KEY` — any random string.
- `INVOICE_API_BASE_URL` — the `invoice` deployment this storefront talks to.
- `INVOICE_API_KEY` — minted on the `invoice` side via `yii api-client/generate`.

```
php yii serve
```

## Tests

```
vendor/bin/testo --suite=Unit
vendor/bin/psalm --no-cache
```
