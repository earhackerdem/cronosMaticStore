# Project Overview — CronosMatic Store

> Generated: 2026-05-07 — Source: code-derived (initial exhaustive scan)

## 1. Purpose

CronosMatic Store is a B2C e-commerce MVP for selling watches in Mexico, priced in **MXN**. The MVP target is a complete, frictionless purchase flow (catalog → cart → checkout → confirmation) plus a basic admin surface for product/category management.

## 2. Project type

| Attribute | Value |
| --- | --- |
| Repository type | Monolith (single Laravel + React codebase) |
| Project type id | `web` (full-stack: server-rendered Inertia pages + dedicated REST API) |
| Primary language | PHP (backend) and TypeScript (frontend) |
| User-facing language | Spanish (UI strings, product data) |
| API language | English (URLs, JSON keys in `snake_case`) |
| Currency | MXN |

## 3. Tech stack summary

| Layer | Technology | Version | Notes |
| --- | --- | --- | --- |
| Runtime (server) | PHP | 8.2+ (CI runs 8.4) | `composer.json` requires `^8.2` |
| Web framework | Laravel | 12.x | `inertiajs/inertia-laravel` 2.x, `tightenco/ziggy` 2.x |
| API auth | Laravel Sanctum | 4.x | Bearer tokens for `/api/v1/*` |
| Web auth | Session (starter kit) | — | For Inertia routes (login, dashboard, settings) |
| ORM | Eloquent | (Laravel 12) | — |
| DB (dev/prod) | MariaDB | 10.11 | Docker compose service `db` |
| DB (tests) | SQLite | in-memory file `database/testing.sqlite` | Configured in `phpunit.xml` |
| Cache / queue / session | Redis | 7-alpine | Driver = `redis` in Docker `dev`/`app` services |
| Frontend runtime | React | 19 | TypeScript 5.7 |
| SPA bridge | Inertia.js | 2.x (`@inertiajs/react`) | Initial page loads only |
| Bundler | Vite | 6.x | `laravel-vite-plugin`, Tailwind plugin |
| CSS | Tailwind CSS | 4.x | `@tailwindcss/vite` |
| UI primitives | Shadcn/UI + Radix | — | `resources/js/components/ui/` |
| Forms | `react-hook-form` + Zod | 7.x / 3.x | — |
| Toasts | `sonner` | 2.x | Mounted globally in `app.tsx` |
| Payments | PayPal REST API v2 | sandbox | `App\Services\PayPalPaymentService` |
| Mail | Laravel Mail (Markdown templates) | — | `App\Mail\OrderConfirmationMail` (queued) |
| Test (backend) | PHPUnit | 11.x | 93 tests (per README) |
| Test (frontend) | Vitest + Testing Library | 3.x / 16.x | 34 tests |
| Test (E2E) | Cypress | 14.x | 11 tests; two configs (local 8000, Docker 3000) |
| Code quality | Laravel Pint, ESLint 9, Prettier 3 | — | `make quality` runs all |
| Container orchestration | Docker Compose | — | Services: `app`, `dev`, `db`, `redis`, `phpmyadmin` |

## 4. Repository structure

```
cronosMaticStore/
├── app/                      Backend PHP (Controllers, Models, Services, Resources, Requests, Middleware)
├── bootstrap/                Laravel bootstrap; middleware aliasing (`admin`)
├── config/                   Laravel config (services.php holds PayPal config)
├── database/                 Migrations (12 files), factories, seeders
├── docker/                   Dockerfiles, nginx.conf, supervisord configs, dev-start.sh
├── routes/                   api.php, web.php, auth.php, settings.php, console.php
├── resources/
│   ├── css/                  Tailwind entry
│   ├── js/                   React 19 + TypeScript SPA-ish frontend (Inertia)
│   │   ├── pages/            Inertia page components (Cart, Checkout, Orders, Products, User, auth/, settings/)
│   │   ├── components/       App-level components + ui/ (Shadcn primitives)
│   │   ├── contexts/         CartContext (global cart state)
│   │   ├── hooks/            use-addresses, use-appearance, etc.
│   │   ├── lib/              api.ts (cart fetch wrapper), address-api.ts (axios), axios.ts (interceptors)
│   │   ├── layouts/          AppLayout, AuthLayout, settings layout
│   │   ├── types/            TypeScript shared interfaces (Product, Cart, Order, Address, etc.)
│   │   └── __tests__/        Vitest specs
│   └── views/                Blade templates (mail templates only in practice)
├── tests/                    PHPUnit Feature/Unit tests
├── cypress/                  E2E specs (cart, products, checkout, address-management)
├── docs/                     This documentation set
├── _bmad/                    BMad framework (workflow tooling)
├── _bmad-output/             BMad-generated artifacts
├── docker-compose.yml        5 services
├── Dockerfile / Dockerfile.dev   Production / development PHP-FPM + Nginx + Node 18
├── Makefile                  Canonical command interface (use `make help`)
├── cypress.config.ts         Local config (port 8000 → `app` service)
├── cypress.docker.config.ts  Docker config (port 3000 → `dev` service)
├── phpunit.xml               SQLite + array drivers for tests
├── composer.json / package.json   Dependencies
└── README.md / DOCKER.md / TESTING.md / PROJECT_CONTEXT.md / CLAUDE.md   Top-level guides
```

## 5. Architecture pattern (one sentence)

A **layered Laravel monolith** with **two parallel HTTP surfaces** sharing models and services: (a) Inertia/web routes (session auth, server-rendered React pages) for auth, dashboard, settings, product browsing, cart and checkout shells; (b) a versioned REST API under `/api/v1/` (Sanctum tokens) consumed by the same React frontend for cart operations, orders, payments and admin CRUD. Business logic lives in `app/Services/` (`CartService`, `OrderService`, `PayPalPaymentService`); controllers stay thin and use `FormRequest` + `JsonResource` for input/output shaping.

See [architecture.md](./architecture.md) for the full breakdown.

## 6. Quick links

- **Master index:** [index.md](./index.md)
- **Architecture (full):** [architecture.md](./architecture.md)
- **API contracts (code-derived):** [api-contracts.md](./api-contracts.md)
- **Data models (code-derived):** [data-models.md](./data-models.md)
- **Source tree (annotated):** [source-tree-analysis.md](./source-tree-analysis.md)
- **Component inventory (frontend):** [component-inventory.md](./component-inventory.md)
- **Development guide:** [development-guide.md](./development-guide.md)
- **Deployment guide:** [deployment-guide.md](./deployment-guide.md)
- **Existing PRD-style docs:** [requirements/user-stories.md](./requirements/user-stories.md), [api/specifications.md](./api/specifications.md), [architecture/system.md](./architecture/system.md), [architecture/data-model.md](./architecture/data-model.md), [PAYPAL_INTEGRATION.md](./PAYPAL_INTEGRATION.md), [IMPLEMENTATION_NOTES.md](./IMPLEMENTATION_NOTES.md), [BACKEND_DISCREPANCIES.md](./BACKEND_DISCREPANCIES.md)

## 7. Getting started (TL;DR)

```bash
make fresh        # First-time: build, install, migrate+seed
make test-all     # Verify environment (138 tests)
make info         # See URLs (app: http://localhost:3000, vite: 5173, phpMyAdmin: 8080)
```

For local (no Docker), see [development-guide.md](./development-guide.md).
