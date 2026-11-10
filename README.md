# headless-ecom API

Production-grade headless ecommerce API in vanilla PHP 8.3+ (MVC, REST, JSON only). Storefront is headless — this repo ships the API that a Next.js/mobile client consumes. Full roadmap: [`headless-ecom-plan.md`](headless-ecom-plan.md); current sprint: [`week-1-plan.md`](week-1-plan.md); agent guide: [`AGENTS.md`](AGENTS.md).

## Architecture

```
Client (Vercel / Mobile) → Nginx → public/index.php (front controller)
  → PSR-15 middleware pipeline (error catch → request-id → CORS → JSON parser → router)
  → Thin controller (PSR-15 handler) → Service → Repository → Postgres / Redis
```

Errors are always RFC 9457 problem details (`application/problem+json`) — never HTML. Logs are Monolog JSON to stdout.

## Stack

| Concern | Library |
|---|---|
| Routing | `nikic/fast-route` |
| HTTP / PSR-7/15/17 | `laminas/laminas-diactoros` |
| DI | `php-di/php-di` |
| Logging | `monolog/monolog` (JSON → stdout) |
| Config | `vlucas/phpdotenv` (CLI fallback only) |
| Quality | `phpunit`, `phpstan` (L8), `phpcs` (PSR-12) |

## Requirements

- PHP >= 8.3, Composer 2
- Docker + Docker Compose

## Quickstart

```bash
make install        # composer install + copy .env.example → .env (once)
make build          # build the app image (first build takes 5-8 min, cached after)
make start          # start app + web + db + cache
make logs           # tail app + web logs
```

Then put a real secret in `.env` (boot fails fast without it):

```bash
openssl rand -hex 32    # paste the output as JWT_SECRET in .env
```

Wait for `(healthy)` on `db`/`cache` in `make start` output, then verify:

```bash
curl http://localhost:8081/          # → {"status":"ok", ...}
curl http://localhost:8081/health    # → {"status":"ok","checks":{...}}
curl http://localhost:8081/nope      # → 404 application/problem+json
```

### Ports (host)

| Service | Host | Inside compose |
|---|---|---|
| API (Nginx) | `8081` | `web:80` |
| Postgres 16 | `5433` | `db:5432` |
| Redis 7 | `6380` | `cache:6379` |

Inside the compose network use `db:5432` / `cache:6379` as configured in `.env`.

## Endpoints (v1)

| Route | Response |
|---|---|
| `GET /` | API welcome/entry point |
| `GET /health` | Status + `{app, database, cache}` checks (DB/Redis checks land Week-2/3) |
| `GET /api/v1` | Version declaration |

- Unknown route → `404` problem+json; wrong method → `405` (lists allowed); invalid JSON body → `400`.
- CORS: strict allowlist from `CORS_ALLOWED_ORIGINS` (never `*`); preflight `OPTIONS` → `204`.
- Every response carries `X-Request-Id`; the same id is in JSON logs.

## Environment

Copy `.env.example` → `.env`. Required keys (missing → fail-fast at boot, never dumps values): `APP_NAME, APP_ENV, APP_DEBUG, APP_URL, DATABASE_URL, REDIS_URL, LOG_LEVEL`. JWT keys are placeholders until Week-2. In prod (Railway/VPS) set real env vars — the app reads the process environment first and only falls back to `.env` for CLI runs (phpunit, seeds). Never commit `.env`.

## Quality gates

```bash
vendor/bin/phpunit                    # both suites
vendor/bin/phpunit --testsuite=Unit   # unit only
vendor/bin/phpstan analyse app config routes public --level=8
vendor/bin/phpcs --standard=PSR12 app config routes public tests
vendor/bin/phpcbf --standard=PSR12 app config routes public tests   # auto-fix
```

Or via Composer scripts: `composer test`, `composer analyse`, `composer cs`.

## Project structure

```
public/index.php          front controller
config/container.php      PHP-DI definitions
routes/api.php            route table
app/Core                  Config, Logger, ErrorHandler, Router, ProblemDetails, emitter
app/Http/Controllers      thin PSR-15 handlers
app/Http/Middleware       pipeline middlewares
app/{Models,Services,Repositories}   W2+ (currently empty)
database/migrations,seeds  W2+
tests/{Unit,Integration}  PHPUnit suites
docker/{php,nginx}        image + server config
docs/                     runbook + learning resources
```

## Conventions (non-negotiable)

- `declare(strict_types=1)`, PSR-12, PHPStan level 8.
- Money as integer cents, timestamps UTC, IDs UUIDv7 (from Week-3+).
- Thin controllers; business logic in Services; SQL only in Repositories.
- API versioning: `/api/v1`; breaking changes → `/api/v2`.
- Never trust client prices; validate every request.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Nginx 403/404 before endpoints exist | Expected until first controller registers — see `week-1-plan.md` Step 6 |
| Port conflict on 8081/5433/6380 | Host ports were remapped from conflict-prone defaults; edit `docker-compose.yml` |
| First `make build` slow | LLVM/GCC toolchain compiles `pdo_pgsql` once; cached afterwards |
| Changed `docker/nginx/default.conf` not applied | Run `make reload` (Nginx reads the mount only at reload) |
| Containers stuck not healthy | `docker compose ps` — wait; image has `pg_isready`/`redis-cli ping` checks |
| 500 with trace in responses | Local-only by contract: `APP_DEBUG=true` + `APP_ENV=local`. In prod keep both off |

## Deploy (target)

API → Railway/Fly/VPS (Docker). Storefront → Vercel. Do **not** deploy this API to Vercel/Netlify. Operations: [`docs/RUNBOOK.md`](docs/RUNBOOK.md). CI pipeline lands in Week-1 Step 11 (deferred).
