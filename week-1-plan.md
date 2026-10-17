# Week-1 Plan — Foundation Only (Manual Implementation, No Code Provided)

## 1. Goal
End the week with a runnable vanilla PHP MVC skeleton that serves JSON over REST, with Docker and CI green. No business features yet.

## 2. Done Criteria (must all pass)
- Composer PSR-4 autoload works; single front controller handles all requests.
- Router returns JSON for defined routes and JSON 404 for undefined.
- Central config from environment, central Monolog JSON logger, central RFC 9457 Problem Details error handler (no HTML stack traces, no PHP warnings leaked).
- Health endpoint responds with status ok plus checks for app, database, cache placeholders.
- Docker Compose up brings Nginx + PHP-FPM + Postgres 16 + Redis 7; app reachable on localhost.
- GitHub Actions runs composer validate, code style check, static analysis, and tests on every push.
- README explains how to install, run, test, and deploy.

## 3. Prerequisites
- PHP 8.3+, Composer 2, Docker + Docker Compose, Git, a GitHub repo, editor with EditorConfig.
- Stripe account not needed this week. No catalog/cart work this week.

## 4. TODO List

Status legend: `[x]` done | `[~]` partial (echoes what remains) | `[ ]` pending

- [x] **Step 1 — Initialize repo and Composer**
  - [x] Project metadata, PHP >=8.3, PSR-4 `App\ → app/` (`composer.json`)
  - [x] Week-1 deps only: fast-route, diactoros, php-di, monolog, dotenv (no Eloquent/Stripe/mailer/S3)
  - [x] Install clean, `composer.lock` committed
- [x] **Step 2 — Folder skeleton**
  - [x] `public/ app/{Core,Http/{Controllers,Middleware},config,routes/database,tests,docker,.github/workflows,docs}`
  - [x] `.gitignore`, `.dockerignore`, `.editorconfig`
- [x] **Step 3 — Environment config module**
  - [x] `.env.example` + `.env` with app/db/redis/log/JWT-placeholder/CORS keys
  - [x] `App\Core\Config` loader: real-env first, Dotenv CLI-only fallback, fail-fast on missing keys, never dumps secrets (verified: phpunit 3 tests, PHPStan L8, PSR-12 clean)
- [ ] **Step 4 — Logger + Error handler**
  - [ ] Monolog PSR-3 JSON to stdout (request id, level, context)
  - [ ] Global handler → RFC 9457 `application/problem+json`; debug trace local-only
  - [ ] Scratch route test for exception → JSON, then remove
- [ ] **Step 5 — Front Controller + DI container**
  - [ ] `public/index.php` bootstrap: autoload → config → container → logger → error handler → dispatch
  - [ ] PHP-DI registrations: config, logger, PSR-7 request/response factories
  - [ ] Nginx confirms only `public/` is reachable
- [ ] **Step 6 — Router + Middleware pipeline**
  - [ ] `routes/api.php` placeholders: welcome, health, version
  - [ ] PSR-15 pipeline: request-id, CORS stub (restrictive), JSON body parser, dispatcher, responder, catcher
  - [ ] Verify: unknown route 404 JSON, wrong method 405 JSON, bad JSON body 400 JSON
- [ ] **Step 7 — Health check contract**
  - [ ] `GET /health`: app status + db/cache (not-configured OK this week), stable shape
- [~] **Step 8 — Dockerize**
  - [x] `docker/php/Dockerfile` (slim: pdo_pgsql, pgsql, opcache; intl/zip/redis deferred to W4; non-root)
  - [x] `docker/php/{opcache.ini,php.ini}`, `docker/nginx/default.conf`
  - [x] `docker-compose.yml`: app/web/db/cache + healthchecks + volumes
  - [ ] Build `app` image (first build slow, one-time) and full cold-start verify from host
- [x] **Step 9 — Makefile / task shortcuts**
  - [x] `install/start-infra/build/start/stop/restart/logs/test/analyse/cs/shell/db-shell/redis-shell`
- [ ] **Step 10 — Tests + Static analysis + Style**
  - [ ] `phpunit.xml` with Unit + Integration suites (1 smoke test passing each)
  - [ ] PHPStan level 8 + PSR-12 phpcs green with zero baseline debt
- [ ] **Step 11 — CI pipeline**
  - [ ] GitHub Actions: validate + cs + analyse + unit tests on push/PR, Composer cache, badge
- [ ] **Step 12 — README + RUNBOOK stub**
  - [ ] Prereqs, env setup, Docker start, test/analyse commands, structure, versioning rule, troubleshooting
- [ ] **Step 13 — Final verification**
  - [ ] Fresh-clone full sequence passes; welcome/health/404 hit from host; restart persistence confirmed
  - [ ] Tag Week-1 milestone commit; note deviations

## 5. What NOT To Do in Week-1
- No users, products, carts, orders, Stripe, mailer, uploads, or ORM wiring.
- No business logic in controllers or route closures.
- No secrets committed, no var_dump or print debugging left in, no HTML error pages.
- No Vercel/Netlify deploy for the API; Docker only.

## 6. Common Pitfalls to Avoid
- Nginx passing wrong script path to FPM (check root + fastcgi params).
- Missing Postgres/Redis PHP extensions in the image.
- CORS wildcard left open; keep restrictive even as stub.
- Logger writing to files instead of stdout (breaks Railway log tail).
- Error handler leaking stack traces when debug flag is on in prod.

## 7. How to Ask for Help
When stuck, share: the step number above, the exact command run, the exact JSON/error output, and the relevant file path and line. Do not paste secrets.
