# Source Tree Analysis — CronosMatic Store

> Generated: 2026-05-07 — Annotated tree derived from filesystem scan.

This document is an annotated map of the repository. Lines marked with **→** describe the purpose of the directory or file, and where applicable show its primary responsibility within the system.

## Top-level layout

```
cronosMaticStore/
├── app/                       → Backend (Laravel) source — see "Backend layout" below
├── bootstrap/                 → Laravel app bootstrap; defines middleware aliases
│   └── app.php                → Configures routing (web/api/console/health), middleware (web group, alias `admin`)
├── config/                    → Laravel config files (sanctum, services [PayPal], cors, ...)
├── database/                  → Migrations, factories, seeders (and SQLite test DB)
├── docker/                    → Dockerfiles' assets (nginx.conf, php/custom.ini, supervisord configs, dev-start.sh)
├── public/                    → Web root (index.php, compiled Vite assets after `npm run build`)
├── resources/                 → Frontend source (React/TS) + Blade views + CSS
├── routes/                    → HTTP route declarations (api.php, web.php, auth.php, settings.php, console.php)
├── storage/                   → Logs, framework caches, public-disk uploads (via `storage` symlink)
├── tests/                     → PHPUnit Feature + Unit tests
├── cypress/                   → E2E specs, fixtures, support
├── docs/                      → Human-readable docs (this directory)
├── _bmad/                     → BMad workflow tooling (skills, customizations, scripts)
├── _bmad-output/              → BMad-generated planning/implementation artifacts
├── .agents/                   → Local agent prompts/configs (not deployed)
├── .github/workflows/         → CI: tests.yml, frontend-tests.yml, lint.yml
├── backups/                   → Local DB backups created by `make db-backup`
├── docker-compose.yml         → 6 services: app, dev, db (mariadb 10.11), db_test (mariadb 10.11 in port 3307, isolated test DB), redis, phpmyadmin
├── Dockerfile                 → Production image (PHP-FPM + Nginx + Node 18 + Cypress deps + xvfb)
├── Dockerfile.dev             → Development image (same base + dev composer/npm + supervisord.dev.conf)
├── docker-setup.sh            → Convenience wrapper to bring up dev/prod
├── Makefile                   → Canonical command surface (services, artisan, tests, quality, db, fresh)
├── composer.json              → PHP dependencies + scripts (`composer test`, `composer dev`)
├── package.json               → JS dependencies + scripts (build, dev, test, lint, types, cypress)
├── vite.config.ts             → Vite config (Laravel plugin, React, Tailwind, host 0.0.0.0:5173)
├── tsconfig.json              → TypeScript config
├── eslint.config.js           → ESLint flat config
├── cypress.config.ts          → E2E config for local (baseUrl http://localhost:8000)
├── cypress.docker.config.ts   → E2E config for Docker (baseUrl http://localhost:3000, retries=2, no-sandbox flags)
├── phpunit.xml                → PHPUnit config (MariaDB via db_test service, array drivers, BCRYPT_ROUNDS=4)
├── .env / .env.example / .env.docker / .env.docker.example   → Environment templates
├── .env.testing               → Loaded by Laravel when APP_ENV=testing; points to db_test/cronosmatic_test
├── PROJECT_CONTEXT.md         → Project conventions + AI rules (Spanish)
├── CLAUDE.md                  → Project-specific guidance for Claude Code
├── DOCKER.md                  → Docker setup details
├── TESTING.md                 → Testing guide (138 tests)
└── README.md                  → User-facing setup guide
```

## Backend layout — `app/`

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php                      → Base controller (Laravel default)
│   │   ├── Api/V1/                             → REST API controllers (JSON, snake_case)
│   │   │   ├── HealthCheckController.php       → GET /api/v1/status, /auth-status
│   │   │   ├── AuthController.php              → register / login / logout / user (Sanctum tokens)
│   │   │   ├── ProductController.php           → Public list + show (active products only, slug)
│   │   │   ├── CategoryController.php          → Public list + show (with paginated products)
│   │   │   ├── CartController.php              → show / addItem / updateItem / removeItem / clear (web middleware)
│   │   │   ├── OrderController.php             → POST /orders (checkout: cart→order→payment→clear)
│   │   │   ├── PaymentController.php           → PayPal: createPayPalOrder / capturePayPalOrder / simulate-success / simulate-failure / verify-config
│   │   │   ├── User/
│   │   │   │   ├── AddressController.php       → CRUD + setDefault for /api/v1/user/addresses (also wired to web.php for session auth)
│   │   │   │   └── OrderController.php         → User order history (auth:sanctum)
│   │   │   └── Admin/                          → Admin endpoints; require auth:sanctum + middleware('admin')
│   │   │       ├── ProductController.php       → apiResource (index, store, show, update, destroy)
│   │   │       ├── CategoryController.php      → apiResource
│   │   │       └── ImageUploadController.php   → POST /admin/images/upload (multipart, stores on `public` disk)
│   │   ├── Auth/                               → Starter-kit Inertia auth controllers (login/register/password/email-verify)
│   │   ├── Settings/                           → Inertia settings controllers (Profile, Password, Address.index)
│   │   ├── Web/
│   │   │   └── UserOrderController.php         → /ajax/user/orders[/{number}] (session auth, JSON for SPA)
│   │   ├── ProductController.php               → Inertia page controller for /productos and /productos/{slug}
│   │   └── PaymentReturnController.php         → /orders/payment/{success,cancel} (PayPal redirect handlers)
│   ├── Middleware/
│   │   ├── EnsureUserIsAdmin.php               → Returns 403 JSON unless `$request->user()->is_admin`
│   │   ├── HandleAppearance.php                → Theme cookie (light/dark)
│   │   └── HandleInertiaRequests.php           → Shares auth, ziggy, sidebarOpen, name, quote
│   ├── Requests/
│   │   ├── Api/V1/                             → API FormRequests (cart, products, categories, orders)
│   │   │   ├── AddCartItemRequest.php
│   │   │   ├── UpdateCartItemRequest.php
│   │   │   ├── ShowProductRequest.php / ListProductsRequest.php
│   │   │   ├── ShowCategoryRequest.php / ListCategoriesRequest.php
│   │   │   ├── StoreOrderRequest.php           → Branches rules by sanctum/web auth (address_id vs embedded address)
│   │   │   └── Admin/
│   │   │       ├── StoreCategoryRequest.php / UpdateCategoryRequest.php
│   │   │       └── Product/StoreProductRequest.php / UpdateProductRequest.php
│   │   ├── StoreAddressRequest.php / UpdateAddressRequest.php   → Used by User/AddressController
│   │   ├── Auth/LoginRequest.php
│   │   └── Settings/ProfileUpdateRequest.php
│   └── Resources/
│       ├── Api/V1/                             → JsonResource shaping for API responses (snake_case)
│       │   ├── ProductResource.php / Admin/ProductResource.php
│       │   ├── CategoryResource.php
│       │   ├── CartResource.php / CartItemResource.php
│       │   ├── OrderResource.php / OrderItemResource.php
│       │   └── AddressResource.php
│       └── AddressResource.php                 → Used by web-routed AddressController
├── Mail/
│   └── OrderConfirmationMail.php               → Markdown mail `emails.orders.confirmation`, queued
├── Models/                                     → Eloquent models (one per table)
│   ├── User.php                                → HasApiTokens, addresses(), cart(), orders(), defaultShipping/Billing
│   ├── Product.php                             → category(), orderItems(), image_url accessor (random fallback by id)
│   ├── Category.php                            → products()
│   ├── Cart.php                                → user(), items(), scopes (forUser/forSession/notExpired); test-aware total accessors
│   ├── CartItem.php                            → cart(), product(), updateTotalPrice(), hasAvailableStock()
│   ├── Order.php                               → constants for status/payment_status, label helpers, canBeCancelled(), isPaid()
│   ├── OrderItem.php                           → boot::saving auto-calculates total_price
│   └── Address.php                             → boot creates/updates: unsets siblings of same type when is_default flips; full_name + full_address accessors
├── Providers/
│   └── AppServiceProvider.php                  → (currently empty)
└── Services/                                   → Business logic (transactional)
    ├── CartService.php                         → getOrCreateForUser/Guest, addProduct, updateQty, remove, clear, mergeGuestCartToUser, validateCartStock
    ├── OrderService.php                        → createOrderFromCart (DB::transaction, decrements stock, creates OrderItems), updatePaymentStatus (sends confirmation email on PAID), cancelOrder (restores stock), getUserOrders, search, stats
    └── PayPalPaymentService.php                → PayPal v2 REST: getAccessToken (basic auth client-credentials), createOrder, captureOrder, simulateSuccessfulPayment / simulateFailedPayment, processPayment helper
```

## Frontend layout — `resources/js/`

```
resources/js/
├── app.tsx                                     → Inertia entry point: createInertiaApp, wraps in <CartProvider>, mounts <Toaster> (sonner)
├── ssr.tsx                                     → SSR entry (used by `npm run build:ssr`)
├── test-setup.ts                               → Vitest setup
├── components/
│   ├── add-to-cart-button.tsx                  → "Añadir al carrito" with stock-aware disabled state
│   ├── cart-indicator.tsx                      → Header cart icon with item count
│   ├── address-card.tsx / address-form.tsx     → Address book UI primitives
│   ├── delete-address-dialog.tsx
│   ├── delete-user.tsx
│   ├── app-header.tsx / app-sidebar.tsx / app-shell.tsx / app-content.tsx / app-sidebar-header.tsx
│   ├── breadcrumbs.tsx / heading.tsx / heading-small.tsx / icon.tsx / input-error.tsx / text-link.tsx
│   ├── nav-footer.tsx / nav-main.tsx / nav-user.tsx
│   ├── user-info.tsx / user-menu-content.tsx
│   ├── app-logo.tsx / app-logo-icon.tsx
│   └── ui/                                     → Shadcn/Radix primitives (alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu, icon, input, label, loading-spinner, navigation-menu, placeholder-pattern, select, separator, sheet, sidebar, skeleton, tabs, toggle, toggle-group, tooltip)
├── contexts/
│   └── CartContext.tsx                         → useReducer-based global cart state; calls CartApi; refreshes on mount
├── hooks/
│   ├── use-addresses.ts                        → CRUD + setDefault wrapper (skips API if guest)
│   ├── use-appearance.tsx                      → Theme switching (light/dark)
│   ├── use-initials.tsx
│   ├── use-mobile-navigation.ts / use-mobile.tsx
├── layouts/
│   ├── app-layout.tsx                          → Authed app shell
│   ├── app/                                    → app-header-layout.tsx, app-sidebar-layout.tsx
│   ├── auth-layout.tsx + auth/                 → Auth split/card/simple layouts
│   └── settings/layout.tsx                     → Settings sidebar
├── lib/
│   ├── api.ts                                  → CartApi (fetch + handleResponse) and UserOrderApi
│   ├── address-api.ts                          → AddressAPI class (axios instance, withCredentials, CSRF init via /sanctum/csrf-cookie)
│   ├── axios.ts                                → Global axios defaults: X-CSRF-TOKEN from meta, Authorization Bearer (auth_token in localStorage), 401 redirect to /login
│   └── utils.ts                                → cn() (tailwind-merge + clsx)
├── pages/
│   ├── welcome.tsx / dashboard.tsx
│   ├── auth/                                   → confirm-password, forgot-password, login, register, reset-password, verify-email
│   ├── settings/                               → addresses, password, profile
│   ├── Products/                               → Index.tsx (catalog grid + filters), Show.tsx (detail page)
│   ├── Cart/Index.tsx                          → Cart page
│   ├── Checkout/Index.tsx                      → Checkout flow (addresses, payment trigger)
│   ├── Orders/Confirmation.tsx                 → Post-checkout confirmation page
│   └── User/                                   → UserOrdersPage.tsx, UserOrderDetailPage.tsx (history & detail)
├── types/
│   ├── index.d.ts                              → Shared interfaces (User, Product, Category, Cart, CartItem, Address, Order, OrderItem, paginated responses, Auth, SharedData [Inertia shared props])
│   ├── global.d.ts
│   └── vite-env.d.ts
└── __tests__/                                  → Vitest specs (components, pages/, hooks/, lib/)
```

## Database — `database/`

```
database/
├── database.sqlite             → Local fallback DB if not using MariaDB
├── testing.sqlite              → PHPUnit DB (per phpunit.xml)
├── migrations/                 → 12 migrations (see data-models.md for full schema)
│   ├── 0001_01_01_000001_create_cache_table.php
│   ├── 0001_01_01_000002_create_jobs_table.php
│   ├── 2014_10_12_000000_create_users_table.php          → users, password_reset_tokens, sessions
│   ├── 2025_05_19_223037_create_personal_access_tokens_table.php   → Sanctum tokens
│   ├── 2025_05_29_210914_add_is_admin_to_users_table.php
│   ├── 2025_05_29_220120_create_categories_table.php
│   ├── 2025_05_30_164215_create_products_table.php       → DECIMAL(8,2) price (≤999,999.99)
│   ├── 2025_06_06_162139_create_carts_table.php          → unique_user_cart, session_id index
│   ├── 2025_06_06_162145_create_cart_items_table.php     → unique_cart_product, FK cascades
│   ├── 2025_06_07_222631_create_addresses_table.php      → type+is_default model, indexes (user_id,type), (user_id,is_default)
│   ├── 2025_06_08_210051_create_orders_table.php
│   ├── 2025_06_08_210058_create_order_items_table.php
│   └── 2025_06_11_213746_modify_addresses_table_for_guest_users.php   → Makes user_id nullable (supports guest orders)
├── factories/                  → AddressFactory, CartFactory, CartItemFactory, CategoryFactory, OrderFactory, OrderItemFactory, ProductFactory, UserFactory
└── seeders/                    → DatabaseSeeder (admin@example.com [is_admin=true], test@example.com), CategorySeeder, ProductSeeder, AdminUserSeeder, CartSeeder, CartItemSeeder
```

## Routes — `routes/`

```
routes/
├── api.php                     → All under prefix `v1` (note: NO global `auth:sanctum` — applied selectively)
│                                 Public: /status, /categories[/{slug}], /products[/{slug}], /auth/{register,login}
│                                 Cart (web middleware!): GET /cart, POST /cart/items, PUT/DELETE /cart/items/{id}, DELETE /cart
│                                 Payments (no auth): /payments/paypal/{create-order, capture-order, simulate-success, simulate-failure, verify-config}
│                                 Orders (web middleware): POST /orders
│                                 Sanctum: /auth-status, /auth/logout, /auth/user, /user/orders[/{order_number}]
│                                 Sanctum + admin alias: /admin/categories (apiResource), /admin/images/upload, /admin/products (apiResource)
├── web.php                     → Inertia: /, /productos, /productos/{slug}, /carrito, /checkout, /orders/confirmation/{orderNumber}
│                                 PayPal returns: /orders/payment/{success,cancel}
│                                 auth+verified: /dashboard, /user/orders[/{orderNumber}], AJAX /ajax/user/orders[/{order_number}]
│                                 auth+verified: /api/v1/user/addresses (Address CRUD over web/session — see BACKEND_DISCREPANCIES.md)
│                                 Includes settings.php and auth.php
├── auth.php                    → Starter-kit Inertia auth (register, login, password reset, email verification, logout)
├── settings.php                → /settings/profile, /settings/password, /settings/addresses
└── console.php                 → Artisan commands (only `inspire`)
```

## Tests — `tests/`

```
tests/
├── TestCase.php                → Base PHPUnit case (Laravel)
├── Feature/                    → HTTP-level tests
│   ├── Api/V1/                 → AuthTest, CartControllerTest, HealthCheckApiTest, PaymentControllerTest, PublicCategoryApiTest, PublicProductApiTest, Admin/(ProductControllerTest, ImageUploadControllerTest), User/(AddressControllerTest)
│   ├── Auth/                   → Starter-kit auth tests
│   ├── Http/Controllers/       → Web ProductControllerTest, Api/V1/Admin/CategoryControllerTest, Api/V1/OrderControllerTest
│   ├── Settings/               → AddressControllerTest, PasswordUpdateTest, ProfileUpdateTest
│   ├── CheckoutFlowTest.php    → End-to-end checkout (still PHPUnit Feature)
│   ├── DashboardTest.php
│   ├── OrderEmailNotificationTest.php
│   ├── PaymentIntegrationTest.php
│   └── Feature/OrderServiceIntegrationTest.php   → Note: nested 'Feature/Feature/' folder; consider flattening
└── Unit/
    ├── AddressModelTest, CartItemTest, CartServiceTest, CartTest, ExampleTest, OrderConfirmationMailTest
    ├── Http/Controllers/Api/V1/OrderControllerTest, Http/Middleware/EnsureUserIsAdminTest
    ├── Models/(CategoryTest, UserTest)
    ├── Services/PayPalPaymentServiceTest
    └── Unit/(OrderItemModelTest, OrderModelTest, OrderServiceTest)   → Note: nested 'Unit/Unit/' folder; consider flattening
```

## Cypress — `cypress/`

```
cypress/
├── e2e/
│   ├── address-management.cy.ts
│   ├── cart-functionality.cy.ts
│   ├── checkout/                → checkout flow specs
│   └── products.cy.ts
├── component/                   → Component testing area (currently disabled in CI per frontend-tests.yml)
├── fixtures/
├── screenshots/
└── support/                     → e2e.ts, component.ts (Cypress hooks/commands)
```

## Critical entry points

| Concern | Entry point |
| --- | --- |
| Laravel app bootstrap | `bootstrap/app.php` |
| Inertia bootstrap | `app/Http/Middleware/HandleInertiaRequests.php` (shares props), `resources/views/app.blade.php` (root template `app`) |
| React bootstrap | `resources/js/app.tsx` (wraps `<App>` in `<CartProvider>`) |
| API request entry | `routes/api.php` (no global Sanctum — be deliberate with middleware) |
| Web request entry | `routes/web.php` |
| Test runner (PHP) | `phpunit.xml` (SQLite, `APP_ENV=testing`) |
| Vite asset build | `vite.config.ts` (entry: `resources/css/app.css`, `resources/js/app.tsx`; SSR: `resources/js/ssr.tsx`) |
| Docker dev start | `docker/dev-start.sh` (run by `dev` service via supervisord.dev.conf) |

## Auxiliary directories

- **`_bmad/`** — BMad framework: `core/`, `bmm/`, `cis/`, `bmb/`, `custom/`, `scripts/` (resolver), and per-module config (`_module-installer/`).
- **`_bmad-output/`** — Generated planning artifacts (PRDs, plans). This documentation set is intentionally written under `docs/` (not `_bmad-output/`) per project convention.
- **`backups/`** — `make db-backup` writes timestamped SQL dumps here (gitignored).
