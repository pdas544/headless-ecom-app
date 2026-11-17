# Headless Ecommerce — Minimal Production Plan

Goal: the smallest surface that can sell real products safely.
Companion to the full 8-week plan (`headless-ecom-plan.md`); when the two disagree, this file wins for MVP scope.

## Essential components

| # | Component | Requirement it fulfills | Minimal scope |
|---|---|---|---|
| 1 | API skeleton | One consistent, observable HTTP contract | Router, middleware, DI, config, RFC 9457 errors, JSON logs, `/health` |
| 2 | Postgres + migrations | Durable source of truth, safe schema evolution | Phinx migrations; UUIDv7 PKs; timestamptz; money in cents |
| 3 | Auth & sessions | Identity, security, authorization | Register/login/refresh/logout/reset/verify; Argon2id; JWT 15 min + rotating refresh; customer/admin RBAC; rate limits |
| 4 | Catalog | Shoppers find and buy; staff create listings | Categories, products, variants; public list/detail with pagination/filter/sort + Postgres FTS; admin CRUD |
| 5 | Inventory | Never oversell | qty + reserved, `SELECT FOR UPDATE` at checkout |
| 6 | Cart | Capture intent without trusting prices | Guest cart token + merge on login; server-side pricing/stock checks; cart in Postgres |
| 7 | Checkout & orders | Correct money and order lifecycle | Idempotency-Key; totals recalculated server-side; transactional order creation; order status machine |
| 8 | Payments | Take real money safely | Stripe PaymentIntent; signature-verified webhook; `webhook_events` for replay/idempotency |
| 9 | Transactional email | Account recovery + purchase receipts | Verify, reset, order confirmation (Symfony Mailer + Twig) |
| 10 | Product media | Visual catalog | Image upload to Cloudflare R2 with type/size validation; no server-side resizing |
| 11 | Ops essentials | Deployable, recoverable, debuggable | Docker, env/secrets, stdout JSON logs, health, Postgres backups + restore drill, CI gates (PHPUnit/PHPStan/PHPCS) |
| 12 | API contract | Frontend integration and safe change | `/api/v1` versioning, `application/problem+json`, README/OpenAPI |

## Minimal data model

- Identity: `users`, `refresh_tokens`, `password_resets`, `email_verifications`, `audit_logs`
- Catalog: `categories`, `products`, `product_variants`, `product_media`, `inventory`
- Sales: `carts`, `cart_items`, `orders`, `order_items`, `payments`, `webhook_events`

Schema rules: UUIDv7 primary keys; `timestamptz` in UTC; all money as integer cents with one configured currency; unique `orders.idempotency_key`; unique `payments.stripe_pi_id`; unique `webhook_events.stripe_event_id`.

## Minimal endpoints

- Auth: register, login, refresh, logout, forgot/reset, verify, `GET /me`
- Public: `GET /categories`, `GET /products`, `GET /products/{slug}`
- Admin: products/categories CRUD, product image upload, `GET /admin/orders`
- Commerce: cart CRUD + merge, `POST /checkout`, `GET /orders`, `GET /orders/{id}`
- System: `POST /webhooks/stripe`, `GET /health`

## Explicitly out of scope (v2)

Wishlists, reviews, coupons, CMS pages/banners/menus, tax zones/shipping rate tables (use one flat tax rate + flat shipping config), multi-currency, multi-warehouse, seller/marketplace, 2FA/social/OAuth, Meilisearch, invoice PDFs, shipment tracking, admin UI.

## Library stack (minimal)

FastRoute, laminas-diactoros, PHP-DI, Eloquent (standalone) + Phinx, symfony/validator, firebase/php-jwt, Predis (rate limiting only), symfony/mailer + twig, stripe/stripe-php, league/flysystem-aws-s3-v3, monolog, PHPUnit/PHPStan/PHPCS.

## Build order

Finish auth (W2) → catalog (W3) → cart + flat tax/shipping (W4 trimmed) → checkout + Stripe (W5) → media + admin order view (W6 trimmed) → hardening + ship.

## Non-negotiable rules

Prepared statements only; validate every input; never trust client prices; calculate totals server-side; idempotent checkout + webhooks; secrets from env; no secrets in logs; JSON logs to stdout; p95 targets: catalog <300 ms, checkout <800 ms.
