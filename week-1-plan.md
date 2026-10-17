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

## 3.1 Read before you build (per step)

Full map + links in `docs/learning-resources.md`. Short version:

| Before step | Read |
|---|---|
| 5 Front Controller + DI | PSR-7/11/15 specs, PHP-DI docs, Fowler "Front Controller" |
| 6 Router + Middleware | FastRoute README + its `src/Dispatcher` source, PSR-15 meta doc |
| 7 Health check | RFC 9110 status codes, 12-factor config |
| 8 Dockerize | Docker get-started, `hub.docker.com/_/php` FPM notes, Nginx `try_files`/fastcgi docs |
| 10 Tests + Static analysis | PHPUnit Test Doubles, PHPStan levels, PSR-12 spec |
| 11 CI | GitHub Actions docs (caching, matrix), "Continuous Delivery" |

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
- [x] **Step 4 — Logger + Error handler**
  - [x] `App\Core\LoggerFactory`: Monolog JSON to stdout, level from `LOG_LEVEL` (invalid → debug), request-id + app_env processor
  - [x] `App\Core\ErrorHandler` + `ProblemDetails` → RFC 9457 `application/problem+json`; `HttpException` status mapping (400/401/403/404/405/409/422/429)
  - [x] Debug trace (`debug` ext member) only when `APP_DEBUG=true` and `APP_ENV=local`; prod hides detail; warnings logged+suppressed, fatals via shutdown
  - [x] Verified: phpunit 10 tests, PHPStan L8, PSR-12 clean, CLI smoke (debug trace present / prod trace absent). Route-based check deferred to Step 6
- [x] **Step 5 — Front Controller + DI container**
  - [x] `public/index.php`: autoload → container (`config/container.php`) → `ErrorHandler::register()` → `ServerRequestFactory::fromGlobals()` → `Kernel::handle()` → `ResponseEmitter`
  - [x] PHP-DI registrations: `Config`, `LoggerInterface`, `ErrorHandler`, PSR-17 `ResponseFactoryInterface`/`StreamFactoryInterface`, `ResponseEmitter`; `Kernel`/`ProblemResponder` autowired
  - [x] `App\Core\ProblemResponder` (RFC 9457 PSR-7 response), `App\Core\ResponseEmitter`, `App\Http\Kernel` (temporary 404 until Step 6)
  - [x] Only `public/` reachable (non-public 404, `.env` 403); `expose_php=Off` + nginx `server_tokens off`
  - [x] Verified: phpunit 14 tests, PHPStan L8, PSR-12 clean, live curl returns `application/problem+json`
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
  - [x] `app` image built (`headless-ecom-app`, ~99MB) and full stack up; db/cache healthy, ports remapped 8081/5433/6380
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

## 8. Learning
- Component map, primary sources, read-real-code targets, schedule: `docs/learning-resources.md`.
