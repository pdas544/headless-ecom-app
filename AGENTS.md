# AGENTS — headless-ecom/api

- Stack: vanilla PHP >=8.3 MVC, FastRoute + laminas-diactoros (PSR-7/15), PHP-DI, Monolog JSON to stdout, phpdotenv local-only fallback.
- Arch: `public/index.php` front controller → PSR-15 pipeline → Controller (thin) → Service → Repository → Postgres/Redis. No SQL in controllers. API `/api/v1`. Errors RFC 9457 (Problem Details; obsoletes RFC 7807) JSON, never HTML.
- Invariants: `declare(strict_types=1)`, PSR-12, money=int cents, time=UTC, IDs=UUIDv7, never trust client price.

## Commands (use these, don't guess)

- `make start-infra` — db+cache only (fast) | `make build` — app image (slow first time, 5-8 min) | `make start` — all without build. Never chain `install && start --build`.
- `composer validate --no-check-publish`
- `vendor/bin/phpunit --testsuite=Unit` on host, or `docker compose exec -T app vendor/bin/phpunit --testsuite=Unit` in container.
- `vendor/bin/phpstan analyse app config routes public --level=8`
- `vendor/bin/phpcs --standard=PSR12 app config routes public tests`
- `make db-shell` / `make redis-shell` — exec inside containers, not host `psql`/`redis-cli`.

## Env / ports gotchas

- Host ports remapped (host uses 8080/5432/6379): web `8081`, pg `5433`, redis `6380`. Inside compose network still `db:5432`, `cache:6379`.
- FPM gets env via compose `env_file: .env` — do NOT require Dotenv in FPM path. Use Dotenv only as CLI fallback (phpunit, seeds) when real env is missing.
- `$_ENV` may be empty if `variables_order` lacks `E` — use a Config helper reading `$_SERVER` + `$_ENV` + `getenv()`.
- Never commit `.env`. Copy from `.env.example`.

## Docker quirks

- First `app` build downloads llvm/gcc toolchain to compile `pdo_pgsql` — one-time cost, cached after. Build once via `make build`, then `make start`.
- `app` has `depends_on: db/cache` with `condition: service_healthy` (`pg_isready`, `redis-cli ping`). Wait for `(healthy)` in `docker compose ps` before hitting the API.
- PHP logs to stdout (`/proc/self/fd/2`), not files. Image runs as non-root `www-data`.

## Scope / boundaries

- Week-1 scope only: skeleton, config, router, logger, health check, Docker, CI. No ORM, Stripe, mailer, S3, or domain logic yet (those land W3-W5).
- `App\` in `app/`: `Http/{Controllers,Middleware,Requests,Resources}`, `Core/*`, `Models|Services|Repositories`. Plus `config/`, `routes/api.php`, `database/migrations|seeds`, `tests/Unit|Integration`.
- Do NOT deploy this API to Vercel/Netlify. API → Railway/Fly/VPS, storefront → Vercel.

## Sources

- `headless-ecom-plan.md` — full 8-week plan, stack rationale, hosting.
- `week-1-plan.md` — Week-1 steps, done criteria, pitfalls.
