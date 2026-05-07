# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

CronosMatic Store is a Laravel 12 + React 19 e-commerce MVP for selling watches (B2C). It is built on top of the Laravel + React + Inertia.js starter kit, but the e-commerce features are deliberately implemented as a **separate REST API under `/api/v1/`** that the React frontend consumes — the Inertia routes (auth, dashboard, settings) coexist alongside the API.

Stack: PHP 8.2+, Laravel 12, MariaDB 10.11, Redis, React 19 + TypeScript + Vite, Tailwind CSS v4, Shadcn/UI, Laravel Sanctum (API auth), Inertia.js (web auth), PayPal payments.

## Development environment

Docker is the primary supported workflow. The `Makefile` is the canonical command interface — prefer `make` targets over running raw `docker compose`, `php artisan`, or `npm` commands, because the Make targets handle the right service (`dev`), env vars (`APP_ENV=testing`), and config files (e.g. `cypress.docker.config.ts`).

The `dev` service exposes Laravel on **http://localhost:3000** and Vite on **5173**. The separate `app` (production) service exposes **8000** — that's why local Cypress (`npm run test:e2e`) targets 8000 while Docker Cypress (`make test-e2e`) targets 3000. **In Docker, always run E2E with `make test-e2e`, not `npm run test:e2e`.**

### Common commands

```bash
make fresh              # First-time setup: build, install deps, migrate+seed
make up / make down     # Start / stop services
make shell              # Bash into the dev container
make migrate-fresh      # Reset DB with seed data (destructive)

# Tests
make test-all           # Backend (PHPUnit) + Frontend (Vitest) + E2E (Cypress)
make test-backend       # PHPUnit only (sets APP_ENV=testing)
make test-frontend      # Vitest only
make test-e2e           # Cypress against the dev container, port 3000
make test-filter FILTER="ProductTest"   # PHPUnit --filter passthrough

# Quality
make quality            # lint + types + pint
make pint               # Laravel Pint (PHP formatter)
make lint               # ESLint --fix
make types              # tsc --noEmit

# Artisan passthrough
make artisan CMD="route:list"
```

If you need to run something without a Make target, the pattern is: `docker compose exec dev <cmd>` (add `-e APP_ENV=testing` for backend tests).

## Architecture

### Two parallel HTTP surfaces

1. **Inertia/web routes** (`routes/web.php`, `routes/auth.php`, `routes/settings.php`) — session-based auth, server-rendered React pages via Inertia. Used for the starter-kit-provided login, dashboard, and settings pages.
2. **REST API** (`routes/api.php`, all under `/api/v1/`) — the e-commerce surface. Sanctum tokens for authenticated endpoints, JSON in/out with `snake_case` keys. Cart routes use the `web` middleware so guest carts work via session, while user carts persist in the DB.

When adding e-commerce features, default to the API path. Only touch Inertia routes if the change is genuinely about the auth/dashboard/settings flows from the starter kit.

### Backend layering (`app/`)

- `Http/Controllers/Api/V1/` — public/customer endpoints (Products, Categories, Cart, Orders, Payment, Auth)
- `Http/Controllers/Api/V1/Admin/` — admin endpoints; protect with the `EnsureUserIsAdmin` middleware in addition to `auth:sanctum`
- `Http/Controllers/Api/V1/User/` — authenticated-user endpoints (Address, Order history)
- `Http/Requests/Api/V1/` — Form Requests for validation; controllers should not validate inline
- `Http/Resources/Api/V1/` — API Resources for response shaping; controllers should never return raw Eloquent models
- `Services/` — business logic lives here, not in controllers. Notable: `CartService` (guest+user cart merge logic), `OrderService` (checkout flow), `PayPalPaymentService` (payment integration)
- `Models/` — `User`, `Product`, `Category`, `Cart`, `CartItem`, `Order`, `OrderItem`, `Address`

### Frontend layering (`resources/js/`)

- `pages/` — Inertia page components (`Cart/`, `Checkout/`, `Orders/`, `Products/`, `User/`, plus starter-kit `auth/`, `settings/`, `dashboard.tsx`)
- `components/ui/` — Shadcn/UI primitives (don't reimplement these; compose them)
- `contexts/CartContext.tsx` — global cart state via React Context (no Redux/Zustand in this project)
- `hooks/`, `services/` (or `api/`), `types/` — custom hooks, API client modules, shared TS types

### Image uploads

Product/category images go through a dedicated endpoint `POST /api/v1/admin/images/upload`, which writes to the Laravel `public` disk and returns a relative path. The Product/Category controllers store that relative path on the model — they do not handle the binary themselves.

## Conventions

- **API routes**: plural English nouns (`/products`, `/categories`), JSON keys in `snake_case`.
- **PHPUnit**: use the `#[Test]` attribute (`use PHPUnit\Framework\Attributes\Test;`), not `/** @test */` doc-comment annotations. Test method names are `snake_case` and descriptive (e.g. `can_show_active_product_detail_with_all_data`).
- **Cypress / Vitest**: describe/it strings in English, `it('should ...')` pattern.
- **UI strings and product data**: Spanish (this is the primary user-facing language). API routes and JSON keys: English. Code comments may be in Spanish per `.cursorrules`, but identifiers must be English.
- **TS files**: `PascalCase.tsx` for components and pages.
- The `app` PHP namespace is `App\` (psr-4); tests live under `Tests\` (`tests/`).

## Testing layout

- Backend feature tests: `tests/Feature/Api/V1/` (one file per controller is the pattern)
- Backend unit tests: `tests/Unit/`
- Frontend unit/integration: `resources/js/__tests__/components/`, `resources/js/__tests__/pages/`
- E2E: `cypress/e2e/`
- The full suite is 138 tests (93 backend + 34 frontend + 11 E2E) — `make test-all` runs all three. Use this to confirm an environment is healthy after `make fresh`.
