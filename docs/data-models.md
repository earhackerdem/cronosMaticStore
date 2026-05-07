# Data Models — CronosMatic Store

> Generated: 2026-05-07 — **Source of truth: migrations + Eloquent models in `app/Models/`** (not the older `architecture/data-model.md`, which has been superseded for some fields — see `docs/IMPLEMENTATION_NOTES.md` and `docs/BACKEND_DISCREPANCIES.md`).

Currency: **MXN** for every monetary field.

## Schema overview

```
users (id, name, email, email_verified_at, password, remember_token, is_admin, timestamps)
└── carts                       (1:0..1)
    └── cart_items              (1:n, UNIQUE cart_id+product_id)
        └── products            (n:1)
            └── categories      (n:1, nullable)
└── addresses                   (1:n, user_id NULLABLE for guest checkout)
└── orders                      (1:n)
    ├── order_items             (1:n, product_id NULLABLE on delete-set-null)
    │   └── products            (n:1, FK SET NULL)
    ├── shipping_address        (n:1, FK addresses, RESTRICT delete)
    └── billing_address         (n:1, nullable, FK addresses, RESTRICT delete)

personal_access_tokens          (Sanctum)
sessions, password_reset_tokens (starter kit)
cache, jobs                     (Laravel infra)
```

## Tables

### `users`

Migration: `2014_10_12_000000_create_users_table.php` + `2025_05_29_210914_add_is_admin_to_users_table.php`.

| Column | Type | Constraints | Notes |
| --- | --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK, AI | |
| `name` | `VARCHAR(255)` | NOT NULL | |
| `email` | `VARCHAR(255)` | UNIQUE, NOT NULL | |
| `email_verified_at` | `TIMESTAMP` | NULLABLE | |
| `password` | `VARCHAR(255)` | NOT NULL | Casted `'hashed'` in model |
| `remember_token` | `VARCHAR(100)` | NULLABLE | |
| `is_admin` | `BOOLEAN` | DEFAULT `false` | Cast `boolean`. Drives `EnsureUserIsAdmin` middleware. |
| `created_at`, `updated_at` | `TIMESTAMP` | NULLABLE | |

Eloquent (`App\Models\User`):

- Traits: `HasApiTokens`, `HasFactory`, `Notifiable`.
- `$hidden`: `password`, `remember_token`.
- Relationships: `cart()` (HasOne), `addresses()` (HasMany), `defaultShippingAddress()` (HasOne, scoped `type=shipping AND is_default=true`), `defaultBillingAddress()` (HasOne, scoped `type=billing AND is_default=true`), `orders()` (HasMany).

### `categories`

Migration: `2025_05_29_220120_create_categories_table.php`.

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK |
| `name` | `VARCHAR(255)` | NOT NULL |
| `slug` | `VARCHAR(255)` | UNIQUE |
| `description` | `TEXT` | NULLABLE |
| `image_path` | `VARCHAR(255)` | NULLABLE — relative path on `public` disk |
| `is_active` | `BOOLEAN` | DEFAULT `true`, cast `boolean` |
| `created_at` / `updated_at` | `TIMESTAMP` | NULLABLE |

Relationship: `products()` (HasMany).

### `products`

Migration: `2025_05_30_164215_create_products_table.php`.

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK |
| `category_id` | `BIGINT UNSIGNED` | FK → `categories(id)` ON DELETE SET NULL, NULLABLE |
| `name` | `VARCHAR(255)` | NOT NULL |
| `slug` | `VARCHAR(255)` | UNIQUE |
| `description` | `TEXT` | NULLABLE |
| `sku` | `VARCHAR(255)` | UNIQUE, NULLABLE |
| `price` | `DECIMAL(8, 2)` | NOT NULL — **range up to 999,999.99** (see BACKEND_DISCREPANCIES.md) |
| `stock_quantity` | `INTEGER` | DEFAULT `0`, cast `integer` |
| `brand` | `VARCHAR(255)` | NULLABLE |
| `movement_type` | `VARCHAR(255)` | NULLABLE — values inconsistent across factory/seeder; standardize to Spanish (`Automático`, `De Cuerda`, `Híbrido`, `Cuarzo`) |
| `image_path` | `VARCHAR(255)` | NULLABLE — relative path on `public` disk |
| `is_active` | `BOOLEAN` | DEFAULT `true` |
| `created_at` / `updated_at` | `TIMESTAMP` | |

Eloquent (`App\Models\Product`):

- `$casts`: `price decimal:2`, `is_active boolean`, `stock_quantity integer`.
- Appends: `image_url`.
- `getImageUrlAttribute()`: returns the stored URL if it starts with `http`, otherwise `url($image_path)`. If `image_path` is null, it picks one of three hardcoded chrono24.com fallback URLs deterministically (by product id mod 3) — keep this in mind in tests and fixtures.
- Relations: `category()` (BelongsTo), `orderItems()` (HasMany).

### `carts`

Migration: `2025_06_06_162139_create_carts_table.php`.

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK |
| `user_id` | `BIGINT UNSIGNED` | FK → `users(id)` ON DELETE CASCADE, NULLABLE |
| `session_id` | `VARCHAR(255)` | NULLABLE, indexed (composite with `expires_at`) |
| `total_amount` | `DECIMAL(10, 2)` | DEFAULT `0`, cast `decimal:2` |
| `total_items` | `INTEGER` | DEFAULT `0`, cast `integer` |
| `expires_at` | `TIMESTAMP` | NULLABLE — set to `now()+7days` for guest carts (see `CartService::getOrCreateCartForGuest`) |
| `created_at` / `updated_at` | `TIMESTAMP` | |

Indexes:

- `unique_user_cart` UNIQUE (`user_id`) — at most one cart per user.
- `(session_id, expires_at)` index.

Eloquent (`App\Models\Cart`):

- Scopes: `forUser($userId)`, `forSession($sessionId)`, `notExpired()`.
- `isExpired()` helper.
- **Important — accessor branch on `app()->environment('testing')`:** `getTotalItemsAttribute` and `getTotalAmountAttribute` recompute from loaded `items` only when in the testing env *and* relation loaded; otherwise they read the stored columns. Keep this in mind when testing cart totals.

### `cart_items`

Migration: `2025_06_06_162145_create_cart_items_table.php`.

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK |
| `cart_id` | `BIGINT UNSIGNED` | FK → `carts(id)` ON DELETE CASCADE |
| `product_id` | `BIGINT UNSIGNED` | FK → `products(id)` ON DELETE CASCADE |
| `quantity` | `INTEGER` | DEFAULT `1`, cast `integer` |
| `unit_price` | `DECIMAL(8, 2)` | NOT NULL — frozen at add-time |
| `total_price` | `DECIMAL(10, 2)` | NOT NULL — `quantity * unit_price` (kept in sync via `CartService`) |
| `created_at` / `updated_at` | `TIMESTAMP` | |

Indexes:

- `unique_cart_product` UNIQUE (`cart_id`, `product_id`) — `addProductToCart` increments quantity rather than inserting.
- `cart_id`, `product_id` indexes.

### `addresses`

Migration: `2025_06_07_222631_create_addresses_table.php` + `2025_06_11_213746_modify_addresses_table_for_guest_users.php` (makes `user_id` nullable).

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK |
| `user_id` | `BIGINT UNSIGNED` | FK → `users(id)` ON DELETE CASCADE, **NULLABLE** (guest checkout) |
| `type` | `VARCHAR(255)` | DEFAULT `'shipping'` — Address::TYPE_SHIPPING / TYPE_BILLING |
| `first_name` | `VARCHAR(255)` | NOT NULL |
| `last_name` | `VARCHAR(255)` | NOT NULL |
| `company` | `VARCHAR(255)` | NULLABLE |
| `address_line_1` | `VARCHAR(255)` | NOT NULL |
| `address_line_2` | `VARCHAR(255)` | NULLABLE |
| `city` | `VARCHAR(255)` | NOT NULL |
| `state` | `VARCHAR(255)` | NOT NULL |
| `postal_code` | `VARCHAR(255)` | NOT NULL |
| `country` | `VARCHAR(255)` | NOT NULL — full name (e.g. `México`), not country code |
| `phone` | `VARCHAR(255)` | NULLABLE |
| `is_default` | `BOOLEAN` | DEFAULT `false`, cast `boolean` |
| `created_at` / `updated_at` | `TIMESTAMP` | |

Indexes: `(user_id, type)`, `(user_id, is_default)`.

Model boot rule (`Address::boot`):

- On `creating` and `updating` to `is_default = true`, *unset* `is_default` on all other addresses with the same `user_id` and `type`. Skipped when `user_id` is null (guest), since guests don't need a default.

Accessors: `full_name` (concat first + last), `full_address` (multi-line formatted).

### `orders`

Migration: `2025_06_08_210051_create_orders_table.php`.

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK |
| `user_id` | `BIGINT UNSIGNED` | FK → `users(id)` ON DELETE SET NULL, NULLABLE |
| `guest_email` | `VARCHAR(255)` | NULLABLE — required for guest orders |
| `order_number` | `VARCHAR(32)` | UNIQUE — pattern `CM-YYYY-XXXXXXXX` (uppercase 8-char random) |
| `shipping_address_id` | `BIGINT UNSIGNED` | FK → `addresses(id)` ON DELETE RESTRICT, NOT NULL |
| `billing_address_id` | `BIGINT UNSIGNED` | FK → `addresses(id)` ON DELETE RESTRICT, NULLABLE |
| `status` | `VARCHAR(50)` | DEFAULT `'pendiente_pago'` |
| `subtotal_amount` | `DECIMAL(10, 2)` | NOT NULL |
| `shipping_cost` | `DECIMAL(10, 2)` | DEFAULT `0.00` |
| `total_amount` | `DECIMAL(10, 2)` | NOT NULL |
| `payment_gateway` | `VARCHAR(50)` | NULLABLE — currently always `'paypal'` |
| `payment_id` | `VARCHAR(255)` | NULLABLE, indexed — capture ID from PayPal |
| `payment_status` | `VARCHAR(50)` | DEFAULT `'pendiente'` |
| `shipping_method_name` | `VARCHAR(100)` | NULLABLE |
| `notes` | `TEXT` | NULLABLE |
| `created_at` / `updated_at` | `TIMESTAMP` | |

Indexes: `(user_id, created_at)`, `(guest_email, created_at)`, `status`, `payment_status`, `payment_id`.

Status enum (constants on `App\Models\Order`):

| Constant | Value | Meaning |
| --- | --- | --- |
| `STATUS_PENDING_PAYMENT` | `pendiente_pago` | Created, awaiting payment capture |
| `STATUS_PROCESSING` | `procesando` | Paid, being prepared |
| `STATUS_SHIPPED` | `enviado` | Dispatched |
| `STATUS_DELIVERED` | `entregado` | Final state (success) |
| `STATUS_CANCELLED` | `cancelado` | Cancelled (auto-restores stock) |

Payment status enum:

| Constant | Value |
| --- | --- |
| `PAYMENT_STATUS_PENDING` | `pendiente` |
| `PAYMENT_STATUS_PAID` | `pagado` |
| `PAYMENT_STATUS_FAILED` | `fallido` |
| `PAYMENT_STATUS_REFUNDED` | `reembolsado` |

Model helpers:

- `canBeCancelled()` — true for `pendiente_pago` or `procesando`.
- `isPaid()` — true when `payment_status === 'pagado'`.
- `getStatusLabel()` / `getPaymentStatusLabel()` — Spanish UI labels.
- `getEmailAttribute()` — returns `user->email` or `guest_email`.

### `order_items`

Migration: `2025_06_08_210058_create_order_items_table.php`.

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | PK |
| `order_id` | `BIGINT UNSIGNED` | FK → `orders(id)` ON DELETE CASCADE |
| `product_id` | `BIGINT UNSIGNED` | FK → `products(id)` ON DELETE SET NULL, NULLABLE |
| `product_name` | `VARCHAR(255)` | NOT NULL — denormalized at purchase time |
| `quantity` | `INT UNSIGNED` | NOT NULL |
| `price_per_unit` | `DECIMAL(10, 2)` | NOT NULL — frozen at purchase time |
| `total_price` | `DECIMAL(10, 2)` | NOT NULL — auto-recomputed on `saving` (model boot) |
| `created_at` / `updated_at` | `TIMESTAMP` | |

Indexes: `order_id`, `product_id`.

Model boot rule (`OrderItem::boot`): `static::saving` recomputes `total_price = quantity * price_per_unit`.

### `personal_access_tokens` (Sanctum)

Standard Laravel Sanctum table (migration `2025_05_19_223037_create_personal_access_tokens_table.php`). Used for API tokens issued by `AuthController::login`/`register`.

### Other Laravel infra tables

`cache`, `jobs`, `sessions`, `password_reset_tokens` — created by starter migrations. The `sessions` table is used because `SESSION_DRIVER=redis` only in Docker; locally `phpunit.xml` uses `array`, and `.env.example` uses `database` driver.

## Entity-Relationship diagram

```mermaid
erDiagram
    USERS ||--o| CARTS : has_active
    USERS ||--o{ ORDERS : places
    USERS ||--o{ ADDRESSES : owns
    CATEGORIES ||--o{ PRODUCTS : groups
    CARTS ||--o{ CART_ITEMS : contains
    PRODUCTS ||--o{ CART_ITEMS : referenced_by
    PRODUCTS ||--o{ ORDER_ITEMS : referenced_by
    ORDERS ||--o{ ORDER_ITEMS : composed_of
    ADDRESSES ||--o{ ORDERS : ships_to
    ADDRESSES ||--o{ ORDERS : bills_to

    USERS { bigint id PK; varchar name; varchar email UK; bool is_admin }
    CATEGORIES { bigint id PK; varchar name; varchar slug UK; bool is_active }
    PRODUCTS { bigint id PK; bigint category_id FK; varchar name; varchar slug UK; varchar sku UK; decimal price; int stock_quantity; varchar brand; varchar movement_type; bool is_active }
    CARTS { bigint id PK; bigint user_id FK; varchar session_id; decimal total_amount; int total_items; timestamp expires_at }
    CART_ITEMS { bigint id PK; bigint cart_id FK; bigint product_id FK; int quantity; decimal unit_price; decimal total_price }
    ADDRESSES { bigint id PK; bigint user_id FK; varchar type; varchar first_name; varchar last_name; varchar address_line_1; varchar city; varchar state; varchar postal_code; varchar country; bool is_default }
    ORDERS { bigint id PK; bigint user_id FK; varchar guest_email; varchar order_number UK; bigint shipping_address_id FK; bigint billing_address_id FK; varchar status; decimal subtotal_amount; decimal shipping_cost; decimal total_amount; varchar payment_gateway; varchar payment_id; varchar payment_status }
    ORDER_ITEMS { bigint id PK; bigint order_id FK; bigint product_id FK; varchar product_name; int quantity; decimal price_per_unit; decimal total_price }
```

## Lifecycle invariants enforced in code

These are **not** DB constraints — they live in services/model boot. Make sure to preserve them in any refactor:

1. **Single default address per (user, type)** — `Address::boot` updates siblings on save (only when `user_id` is set; guest addresses skip this).
2. **One active cart per user** — `unique_user_cart` index. `CartService::getOrCreateCartForUser` finds the non-expired cart or creates one.
3. **Cart totals stay in sync** — `CartService::updateCartTotals` re-reads the items relation and writes `total_amount`/`total_items`. Called after every add/update/remove.
4. **Cart-line `total_price`** — re-set in `CartService::addProductToCart` and `updateCartItemQuantity` (model has a helper `updateTotalPrice()` but services compute inline inside `DB::transaction`).
5. **Order-line `total_price`** — `OrderItem::boot` `saving` callback recomputes it, so manual writes are overwritten.
6. **Stock decrement at order creation** — `OrderService::createOrderFromCart` decrements `products.stock_quantity` for each cart line inside the transaction.
7. **Stock restoration on cancellation** — `OrderService::cancelOrder` increments `products.stock_quantity` for each `order_item` (but only if the product still exists, since `product_id` may be set to NULL after a product deletion).
8. **Order confirmation email on payment success** — `OrderService::updatePaymentStatus` triggers `OrderConfirmationMail` when transitioning to `pagado`. The mail is `ShouldQueue`, so it requires a worker (`make artisan CMD="queue:work"`) outside of the `composer dev` script.
9. **Guest cart merge on login** — `CartService::mergeGuestCartToUser`, called by `CartController::getCartForCurrentUser` whenever an authenticated request comes in with a session. Items that would exceed product stock after the merge are silently dropped/clamped — verify with `validateCartStock` before checkout.

## Soft-delete status

No model uses Soft Deletes. Deletes for `addresses` cascade from `users`. Deleting a `product` referenced by `order_items` sets `order_items.product_id = NULL` (history preserved via `product_name` and `price_per_unit`).
