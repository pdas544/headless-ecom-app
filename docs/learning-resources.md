# Learning Resources — Headless Ecommerce (PHP)

Companion to `week-1-plan.md`. Read a concept, then reproduce the smallest version yourself. Keep notes in `docs/notes/<component>.md`: what it is, why, the 3 APIs/commands you actually use, one gotcha.

## How to study

- 70% build / 30% read. Read the concept once, then rebuild it from scratch without copying.
- Read the library source, not just docs. FastRoute is ~600 lines; PHP-DI and Monolog are readable.
- "Understands X" test: explain it in 3 sentences, then reproduce it unaided.
- Prefer executable sources (this repo's `Makefile`, `composer.json`, `docker-compose.yml`) over blog prose when they disagree.

## Component map (Week-1 scope)

| Area | Primary source of truth | Deep dive | Practice |
|---|---|---|---|
| PHP 8.3 language, types, enums | https://www.php.net/manual/en/langref.php | "PHP 8 Objects, Patterns, and Practice" (Zandstra) | Rewrite `Config` with enums / `readonly` |
| Composer + PSR-4 autoload | https://getcomposer.org/doc/ | Composer autoloading docs | Inspect `vendor/composer/autoload_psr4.php` |
| PSR 3/7/11/12/15 | https://www.php-fig.org/psr/ | PSR-15 meta document | Hand-write a 3-middleware pipeline |
| Front controller + MVC | Fowler, "Patterns of Enterprise Application Architecture" | any framework `index.php` | Draw nginx → FPM → controller flow |
| Routing (FastRoute) | https://github.com/nikic/FastRoute | read `src/Dispatcher` | Add a route with regex param |
| DI container (PHP-DI) | https://php-di.org/doc/ | Inversion of Control | Wire services without autowiring first |
| Config / 12-factor / dotenv | https://12factor.net/config | vlucas/phpdotenv README | Prove prod never loads `.env` |
| Logging (Monolog) | https://github.com/Seldaek/monolog | PSR-3 spec | Add processor + custom handler |
| Errors, HTTP semantics | https://www.rfc-editor.org/rfc/rfc9457 , RFC 9110 | Zalando RESTful API Guidelines | Return 400/404/405/415/422 correctly |
| REST API design | "API Design Patterns" (Geewax), "RESTful Web APIs" (Richardson) | JSON:API spec (contrast) | Write pagination/filter contract first |
| Docker basics | https://docs.docker.com/get-started/ | "Docker Deep Dive" (Poulton) | 2-stage Dockerfile; `docker history` |
| Compose / networks / volumes | https://docs.docker.com/compose/ | https://github.com/docker/awesome-compose | Break+fix `fastcgi_pass` service name |
| PHP-FPM + Nginx | https://hub.docker.com/_/php , https://nginx.org/en/docs/ | FPM pool/fastcgi internals | Explain `try_files`, `SCRIPT_FILENAME` |
| OPcache | https://www.php.net/manual/en/book.opcache.php | opcode/JIT internals | Toggle `validate_timestamps`, observe |
| Postgres | https://www.postgresql.org/docs/ | Kleppmann, "Designing Data-Intensive Applications" | Model users/products; `EXPLAIN ANALYZE` |
| Redis | https://redis.io/docs/ | Kleppmann (caching/consistency) | Cache-aside with TTL by hand |
| Testing (PHPUnit) | https://docs.phpunit.de/ | Test Doubles chapter | TDD the `Config` fail-fast test |
| Static analysis (PHPStan) | https://phpstan.org/ | levels + baseline | L5 → L8, fix each error class |
| Code style (PHPCS/PSR-12) | https://github.com/PHPCSStandards/PHP_CodeSniffer | PSR-12 spec | Add `phpcs.xml` + `phpcbf` loop |
| CI (GitHub Actions) | https://docs.github.com/en/actions | "Continuous Delivery" (Humble/Farley) | Cache Composer, run 4 gates, badge |

## Read real code

- `nikic/FastRoute` — routing internals, tiny.
- `PHP-DI/PHP-DI` — container + autowiring.
- `Seldaek/monolog` — handler → formatter → processor chain.
- Slim or Laminas MVC — how a front controller + PSR-15 pipeline is wired.
- Headless ecommerce references (study models/flow, do not copy): `medusajs/medusa` (Node), `saleor/saleor` (Django), `spree/spree` (Rails).

## Suggested schedule

- Week A: PSR-15 + FastRoute + PHP-DI. Read FastRoute source, rebuild pipeline. Output: notes + hand-rolled pipeline.
- Week B: RFC 9457 + RFC 9110 + REST/API design. Output: endpoint contract doc for `/auth/*` (no code).
- Daily 30 min: Docker/FPM/Nginx internals; Kleppmann ch. 1-3.

## Ecommerce domain queue (W3-W5)

Cart/order state machines · idempotency keys · `SELECT FOR UPDATE` vs optimistic locking · transactional outbox · Stripe PaymentIntent lifecycle + webhook signature verification (Stripe docs: Webhooks, Payment Intents) · inventory reservation.
