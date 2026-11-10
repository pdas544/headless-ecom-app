# Week-2 Plan — Auth, Users, RBAC (Manual Implementation)

## 1. Goal
Real authentication on top of the Week-1 skeleton: a working Postgres data layer (Phinx migrations + Eloquent), UUIDv7 users, Argon2id passwords, short-lived JWT access tokens + rotating opaque refresh tokens, register/login/refresh/logout/forgot/reset/verify-email, `/me`, admin/customer RBAC, login rate limiting, and audit logging. No catalog/cart/checkout yet.

## 2. Done Criteria (must all pass)
- `make migrate` creates the schema from scratch on an empty DB; `make migrate-test` prepares `ecom_test`; `make seed` creates a dev admin from env.
- Register → 201 with user + token pair; duplicate email → 409 problem+json; weak/invalid payload → 422 with field-level `errors`.
- Login → 200 token pair; wrong password and unknown email are indistinguishable (same message, comparable timing); login limited to 5 attempts/15 min per IP+email → 429 with `Retry-After`.
- Refresh rotates (old token rejected), reuse of a revoked token revokes the whole family; logout revokes the presented refresh token; expiry → 401.
- Access token: HS256, 15 min, claims `sub/jti/role/iat/exp/iss`; `GET /me` → 401 without/with malformed/expired token, 200 with valid one.
- `GET /api/v1/admin/users` → 401 unauthenticated, 403 customer, 200 admin (paginated).
- Forgot password always → 202 (no account enumeration); reset token single-use, 1 h TTL, revokes all refresh tokens; verify-email token single-use, 24 h TTL.
- Every auth event (register, login ok/fail, refresh, logout, reset, verify, role gate denial) lands in `audit_logs` with actor/IP/user-agent.
- `/health` now performs real `SELECT 1` + Redis `PING` (bounded timeout); degraded → 503, still stable JSON shape.
- Quality gates green: PHPUnit Unit+Integration, PHPStan L8, PSR-12, `composer validate`.
- CI pipeline is **optional** and deferred to the end of the whole project (owner decision).

## 3. Prerequisites
- Week-1 milestone committed/tagged (the checklist closing step).
- `.env` has `JWT_SECRET` generated: `openssl rand -hex 32`.
- Docker stack running; `argon2id` confirmed available in the image (already verified: `2y, argon2i, argon2id`).
- Read §4 before each step; no business features from W3+ creep in.

## 4. Read before you build (per step)

| Before step | Read |
|---|---|
| 2 Deps/config | firebase/php-jwt README, Symfony Validator "Using the Validator" + attribute constraints, 12-factor config |
| 3 DB foundation | Eloquent "Getting Started" (Capsule), PDO prepared statements, RFC 9562 §5.7 (UUIDv7) |
| 4 Migrations | Phinx docs (migrations, seeds, env vars, Postgres column types) |
| 5 Test DB | PHPUnit fixtures/`setUp`/transactions, Postgres `CREATE DATABASE` notes |
| 6 Validation | Symfony Validator constraints reference, RFC 9457 §3 (extension members) |
| 7 Tokens | OWASP "Password Storage" + "Session Management" cheat sheets, JWT RFC 7519 (§4.1), OWASP refresh-token rotation guidance |
| 8 Auth flows | OWASP "Authentication" + "Forgot Password" cheat sheets, RFC 9110 §15.5 (401/403) |
| 9 RBAC | OWASP "Authorization" cheat sheet, NIST RBAC overview |
| 10 Rate limiting | Symfony RateLimiter docs, "Preventing brute force" OWASP |
| 11 Mail | Symfony Mailer + Mailpit docs |
| 12 Tests | PHPUnit "Test Doubles", integration-test DB isolation patterns |

## 5. Library additions (locked for W2)

| Concern | Choice |
|---|---|
| DB access | `illuminate/database` (Eloquent standalone, Capsule) + `doctrine/dbal` |
| Migrations/seeds | `robmorgan/phinx` |
| JWT | `firebase/php-jwt` |
| Validation | `symfony/validator` (attributes) |
| Redis client | `predis/predis` (pure PHP; no image extension needed) |
| Rate limit / cache | `symfony/rate-limiter` + `symfony/cache` (Redis adapter via Predis) |
| Mail | `symfony/mailer` + `twig/twig`; dev SMTP = Mailpit container |
| Test data | `fakerphp/faker` (already installed) |

Not yet: Stripe, Flysystem/R2, Meilisearch, OAuth2 server, 2FA.

## 6. TODO List

Status legend: `[x]` done | `[~]` partial (echoes what remains) | `[ ]` pending

- [ ] **Step 0 — CI pipeline (OPTIONAL — deferred to end of project, do not start now)**
  - [ ] `.github/workflows/ci.yml`: `composer validate`, PSR-12, PHPStan L8, Unit suite on push/PR (PHP 8.3 via `shivammathur/setup-php`, Composer cache)
  - [ ] Integration suite runs against a Postgres service container (`ecom_test`) once Step 4 exists
  - [ ] Status badge added to `README.md`
- [x] **Step 1 — Dependencies + env contract**
  - [x] `composer require`: illuminate/database 11.51, doctrine/dbal 4.5, phinx 0.16.12, firebase/php-jwt 7.2.1, symfony/{validator,cache,rate-limiter,mailer} 7.4.x, predis 2.4.1, twig 3.30; lockfile updated
  - [x] `composer.json` `config.platform.php=8.3.0` — host PHP 8.4 was resolving Symfony v8 packages (PHP >=8.4) that would break the 8.3 image; re-resolved to 7.4.x
  - [x] `firebase/php-jwt` pinned to `^7.0` (CVE-2025-45769 affects <7.0.0); `composer audit` now clean
  - [x] `.env.example` + `.env`: `JWT_SECRET` (real 64-hex generated locally, never committed), `JWT_TTL=900`, `REFRESH_TTL_DAYS=30` (kept existing name, converted to seconds in config), `MAIL_DSN/MAIL_FROM/MAIL_FROM_NAME`, `ADMIN_EMAIL/ADMIN_PASSWORD` (seed-only, not boot-required)
  - [x] `Config::REQUIRED` extended with `JWT_SECRET`, `JWT_TTL`, `REFRESH_TTL_DAYS`, `MAIL_DSN`, `MAIL_FROM`; all test fixtures updated
  - [x] `config/auth.php`: issuer/audience, TTL bounds (60-3600s; 1-365d), secret >=32 chars fail-fast, refresh cookie flags (Secure off-local, HttpOnly, SameSite=Lax, path `/api/v1/auth`), Argon2id policy (64 MiB, time 4, threads 1)
  - [x] `config/database.php`: parses `DATABASE_URL` (pgsql/postgres schemes, port default, url-decoded credentials, `sslmode`, UTC, ERRMODE_EXCEPTION, EMULATE_PREPARES off)
  - [x] Verified: 40 tests / 113 assertions, PHPStan L8, PSR-12, `composer validate`; container (PHP 8.3.35) loads all new libs + Phinx 0.16.12; `/health` still 200 after env reload
- [ ] **Step 2 — DB foundation**
  - [ ] `App\Core\Uuid`: RFC 9562 UUIDv7 generator (+ unit tests: version/variant, monotonic within same ms)
  - [ ] `App\Core\Database`: PDO factory (ERRMODE_EXCEPTION, prepared-only, UTC session), Eloquent Capsule bootstrap (UTC, no lazy magic docs)
  - [ ] Redis client service (Predis) with DSN from env + short connect/read timeouts
  - [ ] `/health` wired to `SELECT 1` + `PING`; 200 `ok` / 503 `degraded` with per-check `ok|fail`; keep stable shape
  - [ ] Verified: `/health` 200 with db/cache up; stop cache → 503 degraded, app stays up
- [ ] **Step 3 — Migrations + seeds**
  - [ ] `database/migrations` (Phinx, Postgres): `users`, `refresh_tokens`, `password_resets`, `email_verifications`, `audit_logs`
  - [ ] Constraints/indexes: `users.email` unique (lowercased on write), `role CHECK (admin,customer,seller_reserved)`, `refresh_tokens.token_hash` unique, family index, `audit_logs(actor_user_id, created_at)`; all `timestamptz`
  - [ ] `refresh_tokens.family_id`, `revoked_at`, `replaced_by_id`, `expires_at`, `user_agent`, `ip`; `users.password_changed_at`
  - [ ] Phinx config reads env via `$_SERVER/$_ENV/getenv()` (no Dotenv hard dependency); `make migrate`, `make migrate-test`, `make seed`
  - [ ] Dev admin seed from `ADMIN_EMAIL`/`ADMIN_PASSWORD` (idempotent, dev/test only)
  - [ ] Verified: migrate from empty DB, rollback, re-migrate; `\d+ users` reviewed
- [ ] **Step 4 — Test DB harness**
  - [ ] `ecom_test` DB create/reset target; migrations applied before Integration suite (Makefile `test-integration`)
  - [ ] Integration bootstrap: test env (JWT secret fixture, test DB URL), per-test transaction rollback (or truncate helper for auth flows that need cross-request visibility)
  - [ ] `phpunit.xml`: Integration suite env overrides only (unit tests stay env-isolated)
  - [ ] Faker factories for User/RefreshToken (tests only, `Tests\Support`)
- [ ] **Step 5 — Models + repositories**
  - [ ] Eloquent models: `User` (uuid PK, hidden `password_hash`, casts), `RefreshToken`, `PasswordReset`, `EmailVerification`, `AuditLog`
  - [ ] Repositories (SQL/Eloquent calls live here only): `UserRepository`, `RefreshTokenRepository`, `PasswordResetRepository`, `EmailVerificationRepository`, `AuditLogRepository`
  - [ ] Model unit/integration tests: casts, hidden fields, relation refresh-tokens
- [ ] **Step 6 — Validation layer**
  - [ ] `App\Core\Validation\ValidatorFactory` (attribute mapping, no annotations, no auto-mapping errors)
  - [ ] Request DTOs (`app/Http/Requests`): `RegisterRequest` (email, password min 12/max 128, optional name ≤100), `LoginRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest` (token + password), `VerifyEmailRequest`, `AdminUserListRequest` (page/per_page bounds)
  - [ ] 422 Problem Details with `errors: {field: [messages]}` extension; whitelist-only field access
- [ ] **Step 7 — Password + token primitives**
  - [ ] `PasswordHasher`: `PASSWORD_ARGON2ID` (memory 64 MiB, time 4, threads 1), `needsRehash` upgrade path, dummy-hash verify for unknown users (timing equalization)
  - [ ] `Uuid` + `TokenHasher`: opaque refresh/reset/verify tokens = 32 random bytes (base64url), stored as SHA-256 hex only
  - [ ] `JwtIssuer`: HS256, 15 min, `sub/jti/role/iat/exp/iss/aud`; strict verify (alg pinning, leeway 5 s, issuer/audience check)
  - [ ] `RefreshTokenService`: issue/rotate/revoke-family/reuse-detection logic isolated from HTTP
  - [ ] Unit tests: alg confusion rejected, expired/`iat` skew, token hash never equals raw, rotation + family revocation
- [ ] **Step 8 — Auth service + endpoints**
  - [ ] `AuthService` (all flows; controllers stay thin): `POST /api/v1/auth/register` (201), `/login` (200), `/refresh` (200), `/logout` (204), `/forgot-password` (202 always), `/reset-password` (204), `/verify-email` (200)
  - [ ] Responses via `Resources`: user payload (`id,email,name,role,email_verified_at,created_at`) — never `password_hash`
  - [ ] Refresh token deliverable as JSON body + `Set-Cookie` (`HttpOnly; Secure in prod; SameSite=Lax; Path=/api/v1/auth`); refresh/logout accept cookie or body
  - [ ] Reset password revokes all refresh tokens; verify-email stamps `email_verified_at`; tokens single-use + hashed at rest
  - [ ] Audit writes for every flow outcome (success + failure) via `AuditLogger`
  - [ ] Error semantics: 400 malformed body, 401 bad/expired token, 403 role, 409 duplicate email, 422 validation, 429 throttled
- [ ] **Step 9 — Auth + RBAC middleware**
  - [ ] `RequireAuthMiddleware`: parse `Authorization: Bearer`, verify JWT, load user, attach `user`/`auth` request attributes, 401 + `WWW-Authenticate: Bearer`
  - [ ] `RequireRoleMiddleware`: allowlist roles; 403 for wrong role; DI-friendly constructor args
  - [ ] `GET /api/v1/me` (auth) and `GET /api/v1/admin/users` (admin, paginated) proving the gate; `routes/api.php` groups
  - [ ] Ordering fixed: after `RequestId`/`Cors`/`JsonBodyParser`, before handler; middleware resolved via container
  - [ ] Log context gains `user_id` when authenticated
- [ ] **Step 10 — Rate limiting**
  - [ ] Redis-backed `SlidingWindowLimiter` (Symfony RateLimiter + Cache Redis adapter via Predis)
  - [ ] Login: 5/15 min per `IP + sha256(lowercased email)`; register 3/h per IP; forgot-password 3/h per IP+email
  - [ ] 429 problem+json + `Retry-After`; rate-limit hits audited; client IP from trusted proxy header only (config flag), else REMOTE_ADDR
  - [ ] Verified live: 6th login attempt → 429; key isolated per email
- [ ] **Step 11 — Mail (dev)**
  - [ ] Mailpit service in `docker-compose.yml` (`host 8026 → 8025` UI, SMTP 1025 internal), `MAIL_DSN` wired
  - [ ] `MailerService` + Twig templates: verify-email (24 h link → storefront/API), password-reset (1 h link)
  - [ ] CLI smoke: register emits mail visible in Mailpit; links contain single-use tokens (never logged)
  - [ ] Send failures logged (don't fail the request for forgot-password → keeps enumeration-safety)
- [ ] **Step 12 — Tests (unit + integration)**
  - [ ] Unit: Uuid, PasswordHasher, JwtIssuer, TokenHasher, RefreshTokenService rotation/reuse, validator DTOs, rate-limit keying
  - [ ] Integration: register→verify→login→me; login failures indistinguishable; refresh rotation + old token 401 + reuse revokes family; logout revokes; reset revokes all; duplicate email 409; validation 422 shape; unauthenticated 401 (missing/malformed/expired/wrong-signature); customer 403 vs admin 200; 429 threshold; health 200/503
  - [ ] Integration runs in container against `ecom_test` only (never dev DB); `make test-integration`
- [ ] **Step 13 — Docs + hygiene + final verification**
  - [ ] `README.md`: auth endpoints, token model, cookie notes, rate limits, RBAC; `docs/RUNBOOK.md`: rotate `JWT_SECRET` (invalidates access tokens), revoke user sessions SQL, Mailpit usage
  - [ ] `AGENTS.md`: new commands (`make migrate`, `make seed`, `make test-integration`, Mailpit port) + W2 conventions
  - [ ] `docs/learning-resources.md`: W2 section (JWT, OWASP auth/session, UUIDv7, Phinx, Eloquent, Symfony Validator)
  - [ ] Final gate: `composer validate`, full PHPUnit, PHPStan L8, PSR-12, CI green, live curl matrix on a cold stack (register → login → /me → admin gate → 429 → reset)

## 7. What NOT To Do in Week-2
- No catalog/products/cart/checkout/Stripe, no uploads, no CMS, no seller features (role stays reserved).
- No OAuth2 server, social login, 2FA, magic links, or password-history policies.
- No custom crypto: use `password_hash` (Argon2id), CSPRNG, `hash_equals`; never invent token schemes.
- No JWTs stored in `localStorage` assumptions, no refresh tokens in plaintext DB, no secrets in logs/mail bodies.
- No SQL in controllers/services beyond repositories; no raw string-interpolated SQL.
- No wildcard CORS + credentials; no skipping email verification checks on protected writes yet (W3 decides).

## 8. Common Pitfalls to Avoid
- Forgetting `JWT_SECRET`: must fail fast at boot, never fall back to a default key.
- Refresh rotation races: rotate inside a transaction; unique `token_hash` makes double-spend deterministic.
- Reuse detection only revoking the presented token (must revoke the family).
- Enumeration leaks: differing status codes/timing/messages for unknown email vs wrong password; for got-password always 202.
- Cookie flags: `Secure` in prod + `SameSite` interacting with storefront CORS (`Access-Control-Allow-Credentials: true` needed); never `SameSite=None` without `Secure`.
- Phinx/Capsule/env mismatch inside vs outside Docker (`db:5432` vs host `localhost:5433`); migrations run in the `app` container.
- Eloquent timestamps vs `timestamptz`/UTC; `created_at` string comparisons in tests.
- Test DB leakage: integration suite pointing at dev DB would wipe real data — guard by asserting DB name ends with `_test`.
- Rate limiter keying by raw email (case/whitespace variants bypass), or trusting `X-Forwarded-For` from anyone.
- Validation errors mapping to 400 instead of 422, or leaking stack traces through problem extensions.
- Mailer blocking requests: SMTP timeouts must not turn forgot-password into 500.
- PHPStan friction: Eloquent magic properties — add `@property` docblocks on models (keeps L8 clean, no baseline).

## 9. Estimated effort (~30 h)
| Area | Hours |
|---|---|
| Deps/config/DB foundation | 4 |
| Migrations/seeds/test DB | 4 |
| Password/JWT/refresh primitives | 5 |
| Auth flows + middleware/RBAC | 6 |
| Rate limiting + mail | 3 |
| Tests (unit + integration) | 6 |
| Docs/CI/final verification | 2 |

## 10. How to Ask for Help
Share the step number, exact command, exact problem+json/error output, and file:line. Never paste `.env` values or tokens.

## 11. Deviations / carry-over log
- CI pipeline (W1 Step 11 / W2 Step 0) is deferred to the end of the entire project as an optional requirement (owner decision).
- `REFRESH_TTL_DAYS` used instead of `REFRESH_TTL` seconds (matches existing `.env`; converted to seconds in `config/auth.php`).
- `firebase/php-jwt` upgraded to v7 (v6 has CVE-2025-45769).
- Composer platform pinned to PHP 8.3.0 (host is 8.4; Symfony v8 transitive packages would not run in the 8.3 image).
- W1 Step 13 commit/tag still pending (requires explicit commit approval).
