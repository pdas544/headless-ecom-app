# RUNBOOK — headless-ecom API (stub, grows each week)

## Run locally

- Start all services: `make start` (db/cache wait for healthy; app image must exist — `make build`).
- Infra only (fast): `make start-infra` (db + cache, no app image needed).
- Stop everything: `make stop` (keeps data volumes).
- Wipe data: `docker compose down -v` (destructive — pgdata + redisdata are lost).

## Health & smoke

- App health: `curl http://localhost:8081/health` — expect `{"status":"ok",...}`; monitor shape is contract-stable.
- 404 contract: `curl -i http://localhost:8081/nope` — expect `application/problem+json`.
- Auth (Week-2+): register/login flows here.

## Logs

- JSON to stdout: `make logs` (app + web). Do not log to files; Railway/Fly aggregate stdout.
- Each request's `X-Request-Id` matches `request_id` field in log lines — use it for tracing.

## Shells

- App container: `make shell` | Postgres: `make db-shell` | Redis: `make redis-shell`.
- Inside containers: usernames from `.env` (`DB_USERNAME=ecom`, db `ecom`).

## Rebuild flow

- Code changes: nothing to rebuild (volume mount) except `php.ini`/Dockerfile changes → `make build`.
- Nginx config changes: `make reload` (bind mount + reload; no container restart needed).

## Deploy (Week-8, stub)

- Target: Railway/Fly/VPS with Docker. Storefront on Vercel. API never on Vercel/Netlify.
- Staging → prod promotion; secrets via platform env, never committed.

## Backups (Week-8, stub)

- Postgres PVC backup/PITR + restore drill goes here with scheduled dumps.

## Known limitations (Week-1)

- `/health` DB/cache checks are `not-configured` placeholders.
- No CI pipeline yet (Step 11 deferred), no coverage threshold (Week-2).
- intl/zip/phpredis extensions not in image (needed from Week-4); add to Dockerfile then.
