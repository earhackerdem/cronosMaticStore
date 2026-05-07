# Development Guide — CronosMatic Store

> Generated: 2026-05-07 — Code-derived. Authoritative command surface is `Makefile` (run `make help` for the full list).

## Prerequisites

Two supported paths:

| Path | Requirements |
| --- | --- |
| **Docker (recommended)** | Docker + Docker Compose. Nothing else. The `dev` container ships PHP 8.2 + Composer, Node 18 + npm, Cypress system deps (xvfb, libgtk*, etc.), Nginx, supervisord, and PHP Redis extension. |
| **Local (manual)** | PHP 8.2+, Composer, Node.js 22 (CI uses 22; image uses 18 — the lower bound is `^18`), MariaDB/MySQL or SQLite, optionally Redis. |

Tooling versions referenced by CI: PHP **8.4**, Node **22** (`.github/workflows/tests.yml`).

## First-time Docker setup

```bash
make fresh        # docker compose down -v + build --no-cache + up -d
                  # composer install, npm ci, php artisan key:generate, migrate --seed
make info         # Show URLs:
                  #   App (dev):       http://localhost:3000
                  #   Vite HMR:        http://localhost:5173
                  #   App (prod):      http://localhost:8000     ← only when 'app' service runs
                  #   phpMyAdmin:      http://localhost:8080
make test-all     # 138 tests (93 backend + 34 frontend + 11 E2E)
```

Default seeded users (created by `DatabaseSeeder`):

| Email | Role |
| --- | --- |
| `admin@example.com` | Admin (`is_admin=true`) |
| `test@example.com` | Customer |

Passwords come from the factory (`UserFactory`) — for development, log in via the standard `/login` page or use `make tinker` to set a known password.

## Daily commands (Makefile)

### Services

```bash
make up           # Start all services
make down         # Stop services
make restart      # down + up
make rebuild      # build --no-cache + up -d
make status       # docker compose ps
make logs / logs-dev   # tail logs
```

### Shells

```bash
make shell        # bash inside the dev container (run any artisan/composer/npm there)
make shell-db     # bash inside MariaDB container
make shell-redis  # redis-cli
```

### Laravel

```bash
make artisan CMD="route:list"     # generic passthrough
make migrate
make migrate-fresh                # WARNING: drops everything + reseeds
make seed
make tinker
make cache-clear                  # config/cache/route/view clear
make optimize                     # config/route/view cache (don't run in dev)
```

### Dependencies

```bash
make install              # composer install + npm ci
make composer-install / composer-update
make npm-install / npm-update
```

### Build & assets

```bash
make build                # npm run build (production assets)
make dev-assets / make watch   # vite (host 0.0.0.0:5173 — exposed on host port 5173)
```

The Vite dev server is started by supervisord in the `dev` container's `dev-start.sh` automatically. Running `make watch` is only needed if you killed it.

### Tests

All test commands use the **isolated `db_test` MariaDB service** (port 3307 on host, `cronosmatic_test` database). The development database `cronosmatic` is **never touched** — you can run the suite while having local data in dev without losing it.

| Command | What it does |
| --- | --- |
| `make test-all` | Backend + Frontend + E2E (the full suite) |
| `make test-backend` | PHPUnit with `APP_ENV=testing` against `db_test` (MariaDB) |
| `make test-frontend` | `npm run test:run` (Vitest, no DB) |
| `make test-e2e` | Cypress with `cypress.docker.config.ts` (port 3000); first runs `migrate:fresh --seed` on `cronosmatic_test`, then swaps the dev `.env` to point to `cronosmatic_test` for the duration of Cypress |
| `make test-e2e-open` | Same but interactive |
| `make test-e2e-headless` | `npm run test:e2e` against the test DB |
| `make test-db-prepare` | Just (re-)runs `migrate:fresh --seed --force` on `cronosmatic_test`. Useful for debugging seed issues |
| `make test-filter FILTER="ProductTest"` | PHPUnit with `--filter=ProductTest`, against `db_test` |
| `make test-coverage` | PHPUnit with `--coverage`, against `db_test` |
| `make test-parallel` | PHPUnit with `--parallel`, against `db_test` |

#### How the isolation works

- A second MariaDB service `db_test` lives in `docker-compose.yml` (host port `3307`, internal `db_test:3306`). Its data is in the `db_test_data` named volume — separate from `db_data` (which holds `cronosmatic`).
- PHPUnit gets the `db_test` connection through env vars in `phpunit.xml` (overriding the SQLite defaults of the starter kit). The `RefreshDatabase` trait still wraps each test in a transaction, so test data doesn't accumulate.
- Cypress E2E does a one-shot `migrate:fresh --seed --force` against `cronosmatic_test`, then **temporarily rewrites `.env`** inside the `dev` container (`DB_HOST=db_test`, `DB_DATABASE=cronosmatic_test`, `APP_ENV=testing`, `MAIL_MAILER=array`, `PAYPAL_SIMULATE_PAYMENTS=true`) and restarts the Laravel server. After Cypress finishes, the helper `_stop_test_server` restores the original `.env` from `.env.backup-test` and bounces the server back. Vite stays up the whole time.
- Both surfaces use real MariaDB, so there's no SQLite-vs-MySQL parity gap masking bugs.

> ⚠️ **In Docker, always use `make test-e2e`, not `npm run test:e2e`.** The latter targets port 8000 (the `app` service, not `dev`) and won't have the `.env`-swap that points to `db_test`.

### Code quality

```bash
make quality           # lint + types + pint
make lint              # eslint --fix on resources/
make format / format-check    # prettier
make pint              # vendor/bin/pint (Laravel PHP formatter, PSR-12-ish)
make types             # tsc --noEmit
```

CI enforces all of these. PR-time `lint.yml` runs `pint` + `npm run format` + `npm run lint`. `frontend-tests.yml` adds `tsc --noEmit` and Vitest with coverage upload to Codecov on PRs.

### Database

```bash
make db-reset                          # alias for migrate-fresh (destructive)
make db-backup                         # mysqldump → backups/db_backup_YYYYMMDD_HHMMSS.sql
make db-restore FILE=backups/...sql    # mysql < FILE
```

Both backup/restore use environment-driven credentials inside the container (`-T` keeps stdin/stdout for piping).

## Local (no Docker) setup

```bash
# 1. Clone & install
composer install
npm install

# 2. Env
cp .env.example .env
php artisan key:generate

# 3. DB (SQLite shortcut)
touch database/database.sqlite
php artisan migrate --seed

# 4. Run dev (4 processes via concurrently)
composer run dev
# starts: php artisan serve | queue:listen | pail | npm run dev
```

`composer run dev` corresponds to the `dev` script in `composer.json`:

```bash
npx concurrently \
  "php artisan serve" \
  "php artisan queue:listen --tries=1" \
  "php artisan pail --timeout=0" \
  "npm run dev" \
  --names=server,queue,logs,vite
```

For SSR development, use `composer run dev:ssr` (additionally builds and runs `inertia:start-ssr`).

## Environment variables (highlights)

`.env.example` is the canonical template. Notable keys:

| Key | Default (`.env.example`) | Purpose |
| --- | --- | --- |
| `APP_NAME` | `Laravel` | Used by Inertia title, mail subject |
| `APP_ENV` | `local` | `testing` switches PayPal to simulation |
| `APP_URL` | `http://localhost` | Used by `Storage::url`, mail links, PayPal return URLs |
| `DB_CONNECTION` | `sqlite` | Override to `mariadb`/`mysql` for Docker |
| `SESSION_DRIVER` | `database` | Docker compose forces `redis` via env |
| `BROADCAST_CONNECTION` | `log` | |
| `QUEUE_CONNECTION` | `database` | Docker compose forces `redis` |
| `CACHE_STORE` | `database` | Docker compose forces `redis` |
| `MAIL_MAILER` | `log` | Order confirmation mails go to log by default |
| `PAYPAL_MODE` | `sandbox` | `live` only with verified credentials |
| `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET` | placeholder | Required for real PayPal flow |
| `PAYPAL_SIMULATE_PAYMENTS` | `true` | When `true`, `OrderController` calls simulated success path |
| `VITE_APP_NAME` | `${APP_NAME}` | Surfaced to the frontend bundler |

Docker composes its own values via `.env.docker` (and `.env.docker.example`) — when running `make fresh`, the `docker-compose.yml` `environment:` block hardcodes DB/Redis/queue/session/cache vars per service.

## Common workflows

### Adding a new API endpoint (Laravel)

1. Choose surface: e-commerce → `routes/api.php`. Auth area → don't.
2. Generate controller (under `app/Http/Controllers/Api/V1/...`):
   ```bash
   make artisan CMD="make:controller Api/V1/YourController --api"
   ```
3. Add `FormRequest` under `app/Http/Requests/Api/V1/...`:
   ```bash
   make artisan CMD="make:request Api/V1/YourRequest"
   ```
4. Add `JsonResource` under `app/Http/Resources/Api/V1/...`:
   ```bash
   make artisan CMD="make:resource Api/V1/YourResource"
   ```
5. Wire route in `routes/api.php` under `Route::prefix('v1')->group(...)`. Wrap in `Route::middleware('auth:sanctum')` (and `'admin'` for admin-only).
6. If the route mutates state, decide whether session middleware is needed (cart/orders use `web` middleware so `X-CSRF-TOKEN` and cookies work).
7. Push business logic into a Service under `app/Services/`. Inject via constructor.
8. Tests:
   - Feature test under `tests/Feature/Api/V1/...` covering 200/422/403/404 paths.
   - Unit tests for service methods under `tests/Unit/Services/...`.
   - Use `#[Test]` attribute (project convention; not the `/** @test */` comment style).

### Adding a new React page (Inertia)

1. Create `resources/js/pages/MyFeature/Index.tsx` (PascalCase).
2. Add a route in `routes/web.php`:
   ```php
   Route::get('/mi-feature', fn () => Inertia::render('MyFeature/Index'))->name('web.myfeature.index');
   ```
3. Inertia auto-discovers it via `resolvePageComponent('./pages/${name}.tsx')`.
4. For client-only data, use `fetch` with `lib/api.ts` patterns or `lib/axios.ts` (which already attaches `Authorization` from `localStorage('auth_token')` and handles 401).
5. Tests: add a Vitest spec under `resources/js/__tests__/pages/MyFeature/Index.test.tsx`. Reuse `__tests__/utils/test-utils.tsx` for the test harness.

### Adding a database migration

```bash
make artisan CMD="make:migration create_foo_table"
# edit database/migrations/...
make migrate
```

Don't forget to register seeders in `DatabaseSeeder::run()`. Add a factory if you need test data.

### Running a one-off PHP command

```bash
make shell
> php artisan tinker
```

or

```bash
docker compose exec dev php artisan tinker
```

## Troubleshooting

| Symptom | Cause / fix |
| --- | --- |
| `Cypress failed to verify that your server is running` | Using `npm run test:e2e` while only the `dev` service is up. Use `make test-e2e` instead. |
| `service "dev" is not running` when running `make test-*` | Start the stack first: `make up`. The dev service must be up for any test target. |
| `service "db_test" is not running` | After pulling the new compose file you may need `make up` once (it creates the new container). |
| `404: Not Found` for `/productos/reloj-automatico-test` during E2E | The test DB wasn't seeded. Run `make test-db-prepare` (or just `make test-e2e`, which does it for you). The dev DB (`cronosmatic`) intentionally doesn't get these test fixtures — they live only in `cronosmatic_test`. |
| `.env` looks wrong after a killed `make test-e2e` | The teardown step restores `.env` from `.env.backup-test`. If a run was killed mid-flight, run manually: `docker compose exec dev mv .env.backup-test .env && docker compose restart dev`. |
| Backend tests hang on Sanctum / sessions | `APP_ENV=testing` is required (Make targets set it; `php artisan test` directly may not). |
| 419 Page Expired in `/api/v1/cart/...` | CSRF token expired or `X-Session-ID` missing. The frontend auto-resets via `/sanctum/csrf-cookie`. |
| Order confirmation email not visible | Mail driver is `log` by default. Watch `storage/logs/laravel.log`. The mail is queued (`ShouldQueue`); ensure a worker is running (`composer run dev` includes `queue:listen`). |
| PayPal calls fail with `Failed to get PayPal access token` | Missing `PAYPAL_CLIENT_ID` / `PAYPAL_CLIENT_SECRET`. For development, set `PAYPAL_SIMULATE_PAYMENTS=true`. |
| Vite HMR not connecting | The `dev` container exposes 5173. Check `vite.config.ts` `server.host` / `hmr.host`. WSL2 sometimes needs `host: '0.0.0.0'`. |
| `permission denied` writing to `storage/` after rebuild | Run `docker compose exec dev chown -R www-data:www-data /var/www/html` (also in DOCKER.md). |
| New PHP extension missing | Add it to `Dockerfile`/`Dockerfile.dev` (`docker-php-ext-install ...`) and `make rebuild`. |

## Conventions cheat-sheet

| Concern | Convention |
| --- | --- |
| API URLs | English plural (`/products`, `/categories`). |
| JSON keys | `snake_case` (e.g. `total_amount`). |
| PHP code style | PSR-12 enforced by Pint. |
| PHP test annotations | `#[Test]` attribute (`use PHPUnit\Framework\Attributes\Test;`); test methods in `snake_case`. |
| TSX file names | `PascalCase.tsx` for components and pages. |
| UI strings, product data | Spanish. |
| Comments | Spanish allowed (per `.cursorrules`). Identifiers must be English. |
| API responses | Use `JsonResource` — never raw Eloquent models. |
| Validation | Use `FormRequest` — don't validate inline in controllers. |
| Business logic | Lives in `app/Services/` (transactional via `DB::transaction`). |
| Image uploads | Use `POST /api/v1/admin/images/upload`; store the returned relative `path` on the model's `image_path` field. |
| Cart access | Always go through `App\Services\CartService`. Never new up `Cart`/`CartItem` directly in a controller. |

For project-wide AI rules, see `.cursorrules` and `CLAUDE.md`.
