# Week-1 Plan — Foundation Only (Manual Implementation, No Code Provided)

## 1. Goal
End the week with a runnable vanilla PHP MVC skeleton that serves JSON over REST, with Docker and CI green. No business features yet.

## 2. Done Criteria (must all pass)
- Composer PSR-4 autoload works; single front controller handles all requests.
- Router returns JSON for defined routes and JSON 404 for undefined.
- Central config from environment, central Monolog JSON logger, central RFC7807 error handler (no HTML stack traces, no PHP warnings leaked).
- Health endpoint responds with status ok plus checks for app, database, cache placeholders.
- Docker Compose up brings Nginx + PHP-FPM + Postgres 16 + Redis 7; app reachable on localhost.
- GitHub Actions runs composer validate, code style check, static analysis, and tests on every push.
- README explains how to install, run, test, and deploy.

## 3. Prerequisites
- PHP 8.3+, Composer 2, Docker + Docker Compose, Git, a GitHub repo, editor with EditorConfig.
- Stripe account not needed this week. No catalog/cart work this week.

## 4. Step-wise Guidelines

### Step 1 — Initialize repo and Composer
- Create project metadata with PHP >=8.3 requirement and PSR-4 autoload pointing to the app directory.
- Add the Week-1 only dependencies: router, PSR-7 implementation, DI container, logger, dotenv, plus dev tools for tests, static analysis, code style. Do not add Eloquent, Stripe, mailer, or S3 yet — keep Week-1 lean.
- Verify install with no version conflicts and commit lock file.

### Step 2 — Create folder skeleton
- Create directories for: public entry point, application core, HTTP layer (controllers, middleware), configuration, routes, database placeholders, tests, docker files, CI workflows, docs.
- Add gitignore for vendor, environment files, IDE files, logs, coverage output.
- Add EditorConfig for consistent line endings and indentation.

### Step 3 — Environment config module
- Create a config loader that reads from environment variables with dotenv fallback for local only.
- Define keys for app name, environment, debug flag, base URL, database URL, Redis URL, log level, JWT secrets placeholders (empty for now).
- Ensure missing required keys fail fast with a clear message in local, but never dump secrets in output or logs.
- Provide a documented example env file.

### Step 4 — Logger + Error handler
- Set up a single shared PSR-3 logger that writes structured JSON to stdout (Docker-friendly) with request id, level, message, context.
- Build a global exception and error handler that converts all throwables and PHP errors into RFC7807-style JSON responses with appropriate HTTP status codes.
- In production mode hide details; in debug mode include trace only locally. Test by triggering a fake exception on a scratch route, then remove it.

### Step 5 — Front Controller + DI container
- Point all web traffic to a single public index file via Nginx. That file should bootstrap autoload, config, container, logger, error handler, then dispatch.
- Register core services in the DI container: config, logger, request/response factories. No domain services yet.
- Verify no direct script access outside the public directory.

### Step 6 — Router + Middleware pipeline
- Define a routes file for API v1 with only placeholder routes for now: a welcome route, a health route, and a version route.
- Implement a minimal PSR-15 style middleware pipeline: request id, CORS stub (restrictive allowlist), JSON body parser, router dispatcher, JSON responder, error catcher.
- Controllers must be thin: accept request, call nothing yet or a stub service, return JSON resource. No DB calls this week.
- Verify unknown route returns JSON 404, wrong method returns JSON 405, invalid JSON body returns JSON 400.

### Step 7 — Health check contract
- Implement a health endpoint that returns app status and dependency statuses. Database and Redis checks can return a not-configured state this week — wire real checks in Week-2/3.
- Keep response shape stable since Railway/Render and uptime monitors will use it.

### Step 8 — Dockerize
- Create PHP-FPM image definition with required extensions for Postgres, Redis, intl, zip, opcache. Non-root user if possible, production OPcache settings documented.
- Create Nginx server block that forwards PHP to FPM, blocks access to dotfiles, sets security headers, raises client body size for future uploads.
- Create Compose file with four services: app (php-fpm), web (nginx), db (postgres 16 with named volume + healthcheck), cache (redis 7 with persistence + healthcheck).
- Verify full cold start works: remove volumes, bring up, hit welcome and health endpoints from host.

### Step 9 — Makefile / task shortcuts
- Provide short documented tasks for: install, start, stop, logs, test, analyze, style check, format, shell into app container, db shell. Keep names stable for CI reuse.

### Step 10 — Tests + Static analysis + Style
- Set up PHPUnit with unit suite and integration suite placeholders (one passing smoke test each, e.g. health response shape).
- Set up PHPStan at max level and code style checker for PSR-12. Fix all baseline issues now — do not carry tech debt into Week-2.
- Aim for green on empty coverage; coverage threshold enforcement starts Week-2.

### Step 11 — CI pipeline
- Add a workflow that on push and pull request runs: dependency validation, style check, static analysis, unit tests. Cache Composer dependencies.
- Require the workflow to pass before merge. Add status badge to README.

### Step 12 — README + RUNBOOK stub
- Document prerequisites, env setup, Docker start, how to run tests and checks, project structure explanation, API versioning rule, commit convention.
- Add a short troubleshooting section for common Docker port conflicts, permission issues, and missing extensions.

### Step 13 — Final verification
- Run the full sequence from scratch: fresh clone, copy example env, install, start containers, run checks and tests, hit welcome/health/404 routes, stop and restart to confirm persistence.
- Tag the commit as the Week-1 milestone and note any deviations for Week-2.

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
