# Headless Ecommerce Plan — Vanilla PHP MVC + REST API

## 0. Assumptions (locked)
- Single developer, ~30h/week, 8 weeks
- Vanilla PHP 8.3+, MVC-based, RESTful API only (headless, no server-rendered storefront)
- Payments: Stripe (PaymentIntent + Webhooks)
- Seller/marketplace: deferred to v2, but schema kept extensible
- Hosting: API NOT on Vercel/Netlify

## 1. Architecture
Client (Vercel storefront / Mobile) -> Cloudflare -> Nginx -> public/index.php (Front Controller) -> PSR-15 Middleware pipeline -> Router -> Controller (thin) -> Service (business logic) -> Repository -> Postgres / Redis

- Pattern: MVC + Service + Repository. Controllers only parse request and return JSON resource. No SQL in controllers.
- API versioning: `/api/v1`. Breaking change => `/api/v2`.
- Auth: JWT access token short-lived (15 min) + opaque rotating refresh token (httpOnly cookie or body for mobile). Passwords with Argon2id.
- Money: integer cents everywhere. Time: UTC timestamptz. IDs: UUIDv7. Prices never trusted from client.

Project structure:
- app/Http/Controllers, Middleware, Requests (validation DTOs), Resources (JSON presenters)
- app/Models, Services, Repositories, Core (Router, Container, Config, Logger, ErrorHandler)
- config/, routes/api.php, public/index.php, database/migrations, database/seeds, tests/
- docker/, .github/workflows/, docs/

## 2. Library Stack (Composer)

| Concern | Choice | Why |
|---|---|---|
| Routing | nikic/fast-route | Fast, proven, minimal, suits vanilla MVC |
| HTTP + Middleware | laminas/laminas-diactoros (PSR-7/15) | Standard PSR pipeline |
| DI Container | php-di/php-di | Autowiring, solo-dev productive |
| DB driver + abstraction | PDO_pgsql + doctrine/dbal | Safe Postgres access |
| ORM | illuminate/database (Eloquent standalone) | Fast to ship solo; familiar ActiveRecord |
| Migrations + Seeds | robmorgan/phinx | Simple, framework-agnostic |
| Validation | symfony/validator | Attribute-based, robust |
| Auth | firebase/php-jwt + Argon2id | Minimal JWT; no full OAuth server needed for v1 |
| Logging | monolog/monolog (PSR-3, JSON to stdout) | Docker/Railway log friendly |
| Config | vlucas/phpdotenv | 12-factor env |
| Cache / Queue / Rate-limit | predis/predis (Redis) + symfony/cache, symfony/rate-limiter | Cart cache, throttling, jobs |
| API docs | zircote/swagger-php (OpenAPI 3.1) | Contract for frontend |
| Quality | phpunit/phpunit, phpstan (L8), squizlabs/php_codesniffer (PSR-12), fakerphp/faker | Prod-grade gate |
| Payments | stripe/stripe-php | Required |
| Mail + Templates | symfony/mailer + twig/twig | Order + auth mails |
| Uploads + Images | league/flysystem-aws-s3-v3 + intervention/image | R2/S3 compatible |
| Search v1 / v2 | Postgres FTS (tsvector + pg_trgm) / Meilisearch later | Enough to ~100k SKUs |

Alternative if enterprise DDD: doctrine/orm + doctrine/migrations instead of Eloquent + Phinx.

## 3. Hosting (recommendation)

Do NOT host the PHP API on Vercel/Netlify. They are for static/serverless Node. PHP needs long-running FPM + persistent PDO + reliable webhooks.

- API + Postgres + Redis: Railway (recommended for solo MVP, $5-20/mo, 1-click DBs) → Fly.io or Render for prod. Cheapest at scale: Hetzner VPS 4GB + Coolify + Cloudflare.
- Storefront demo: Vercel (correct use).
- Media: Cloudflare R2 (S3-compatible, zero egress).
- DNS/CDN/WAF: Cloudflare. Secrets: Railway/Render env, never committed.

## 4. Data Model (v1, seller-ready)
users(id, email unique, password_hash, role[admin,customer,seller_reserved], email_verified_at) → refresh_tokens, password_resets
categories(id, slug unique, parent_id) → products(id, slug unique, status, vendor_id NULL for v2, price_cents, currency) → product_variants(sku unique), attributes, inventory(product_id, qty, reserved), media
carts(token UUID, user_id nullable), cart_items, wishlists, coupons(code unique, rules JSON), taxes, shipping_zones
orders(number unique, user_id, status, totals_cents, idempotency_key unique), order_items, payments(stripe_pi_id unique, status), shipments, invoices, audit_logs, outbox_events (for webhooks/jobs), pages/banners/menus/reviews for CMS.

Indexes: products(slug,status), variants(sku), orders(user_id,status,created_at), FTS index on products name/description.

## 5. Week-wise Plan (8 weeks)

W1 Foundation: Composer PSR-4 skeleton, Front Controller, Router+DI+Config+Logger, RFC7807 JSON errors, GET /health, Docker Compose (php-fpm+nginx+postgres16+redis7), Makefile, GitHub Actions (validate, phpcs, phpstan, phpunit).
W2 Auth/Users/RBAC: register/login/refresh/logout, forgot/reset, verify email, JWT middleware, admin/customer gate, login rate-limit, audit log.
W3 Catalog + Inventory: categories/products/variants/media CRUD (admin) + public list/detail with pagination/filter/sort/search, Redis cache + ETag.
W4 Cart/Wishlist/Promotions: guest cart UUID merge on login, wishlist, coupon validate + calculator service (tax/shipping/discount server-side).
W5 Checkout/Orders/Stripe: transactional checkout with row lock, PaymentIntent, webhook verify + idempotency + outbox retry, order mails + invoice.
W6 Admin CMS + Reviews + Uploads: admin CRUD, pages/banners/menus, reviews/ratings, R2 upload + resize, cursor pagination.
W7 Hardening + Quality: CORS, headers, rate-limits, OpenAPI + Postman, PHPUnit >70% (unit+integration on test DB), PHPStan L8, k6 smoke.
W8 Ship: multi-stage Dockerfile (non-root, OPcache+JIT), prod env on Railway, backup/PITR, staging→prod, README + RUNBOOK, p95 targets (catalog <300ms, checkout <800ms).

## 6. Core Endpoints (v1)
POST /api/v1/auth/register,login,refresh,logout,forgot-password,reset-password,verify-email | GET /me
GET /products, /products/{slug}, /categories, /search | Admin: POST/PUT/DELETE /admin/products, /admin/categories
GET/POST /carts, /carts/items, /carts/merge | POST /coupons/validate | GET/POST /wishlists
POST /checkout | GET /orders, /orders/{id} | POST /webhooks/stripe | Admin: /admin/orders, /admin/users, /admin/pages, /admin/uploads

## 7. Production Best Practices
1. Thin controllers / fat services / repositories for SQL.
2. strict_types, PSR-12, prepared statements only.
3. Validate every input DTO; return RFC7807 problem JSON.
4. Recalc totals server-side; lock inventory with SELECT FOR UPDATE.
5. Idempotency-Key on checkout; unique constraint in DB.
6. Verify Stripe signature; retry webhooks via outbox/queue.
7. Audit log for admin writes, stock changes, logins.
8. Rate-limit auth + checkout; security headers + CORS allowlist.
9. No secrets in git; health check; JSON logs; backup + restore drill.

## 8. Suggestions
1. Do not hand-roll ORM/auth/validator — use the stack above.
2. Keep seller extensibility without building it: role enum + vendor_id nullable + policy stub.
3. Defer to v2: multi-currency, multi-warehouse, CMS builder, Meilisearch, OAuth2 server (league/oauth2-server only if third-party apps need it).
4. Build admin as pure REST too (same API, RBAC-gated) to stay truly headless.
