# API Contracts — CronosMatic Store

> Generated: 2026-05-07 — **Source of truth: `routes/api.php`, `routes/web.php`, controllers and `app/Http/Resources/Api/V1/`** (this supersedes `docs/api/specifications.md` where they differ).

## Conventions

- **Base URL:** `/api/v1` (e.g., `http://localhost:3000/api/v1`).
- **Encoding:** JSON request/response, `snake_case` keys, English resource paths.
- **Authentication:**
  - **Public:** `/status`, `/categories`, `/categories/{slug}`, `/products`, `/products/{slug}`, `/auth/register`, `/auth/login`.
  - **Web/session middleware (the API endpoint runs inside the `web` middleware group, so cookies + CSRF apply):** `/cart`, `/cart/items[/{id}]`, `POST /orders`. The frontend sends an `X-Session-ID` header to keep guest carts stable across CSRF resets.
  - **Sanctum bearer:** `/auth-status`, `/auth/logout`, `/auth/user`, `/user/orders[/{order_number}]`, all `/admin/*`.
  - **Sanctum + `admin` middleware (`EnsureUserIsAdmin`):** all `/admin/*`.
- **Address management routes are NOT in `routes/api.php`** — they live under `routes/web.php` at `/api/v1/user/addresses` and use **session auth** (intentional, see `docs/BACKEND_DISCREPANCIES.md`). The frontend `AddressAPI` warms the CSRF cookie via `GET /sanctum/csrf-cookie` before each write.
- **Error responses (Laravel default):** validation → `422 { message, errors: { field: [..] } }`. Most controllers also return their own success/failure envelope (`{ success: bool, message, data?, errors? }`).

## Status / health

### `GET /api/v1/status`
- Auth: public.
- Response 200: `{ "status": "ok", "message": "API is running", "timestamp": "<iso8601>" }`.

### `GET /api/v1/auth-status` (auth:sanctum)
- Returns the authenticated user payload + `"status": "ok"`. 401 if no token.

## Authentication (`/auth`)

Implementation: `app/Http/Controllers/Api/V1/AuthController.php`.

### `POST /api/v1/auth/register` (public)
- Body: `{ name, email, password, password_confirmation }`
- Validation: `name` required string ≤255; `email` required, unique; `password` required min 8, confirmed.
- Response 201: `{ "data": { "user": <User>, "token": "<plainTextToken>" } }`.
- Note: token is created with abilities `['*']` via `createToken('auth_token')`.

### `POST /api/v1/auth/login` (public)
- Body: `{ email, password }` (no `remember_me` field — declared in spec but ignored in code).
- Errors: `422` with `errors.email = ["Las credenciales proporcionadas son incorrectas."]` on bad creds.
- Response 200: `{ "data": { "user": <User>, "token": "<plainTextToken>" } }`.

### `POST /api/v1/auth/logout` (sanctum)
- Deletes `currentAccessToken()`. Response 204.

### `GET /api/v1/auth/user` (sanctum)
- Response 200: `{ "data": <User> }`.

## Products (`/products`)

Implementation: `app/Http/Controllers/Api/V1/ProductController.php`. Resource: `App\Http\Resources\Api\V1\ProductResource`.

### `GET /api/v1/products` (public)
- Query (validated by `ListProductsRequest`):
  - `category` (string, slug — note: the existing spec calls this `category_slug`, but the **code uses `category`**).
  - `search` (string) — matches `name`, `description`, `sku`.
  - `sortBy` (string), `sortDirection` (`asc`/`desc`). Default order: `created_at desc` when `sortBy` not provided.
  - `per_page` (int, default 15).
- Filters: `is_active=true` always; if `category` is set, also requires the category to be active.
- Response 200: paginated `ProductResource` collection (`data[], links, meta`).

### `GET /api/v1/products/{slug}` (public)
- Resolves by `slug` + `is_active=true`, eager-loads `category`.
- 404 when not found / inactive.
- Response 200: single `ProductResource`.

`ProductResource` shape:

```json
{
  "id": 1, "name": "...", "slug": "...", "description": "...",
  "sku": "...", "price": 4999.99, "stock_quantity": 12,
  "brand": "...", "movement_type": "Automático",
  "image_path": "products/uuid.jpg",
  "image_url": "http://host/storage/products/uuid.jpg",
  "is_active": true,
  "category": { ... } /* whenLoaded */,
  "created_at": "Y-m-d H:i:s", "updated_at": "Y-m-d H:i:s"
}
```

`image_url` is **never null**: products without `image_path` get a deterministic fallback from a hardcoded list of three chrono24.com URLs (selected by `id mod 3`).

## Categories (`/categories`)

Implementation: `app/Http/Controllers/Api/V1/CategoryController.php`.

### `GET /api/v1/categories` (public)
- Lists active categories paginated (`per_page` default 15).
- Response 200: `CategoryResource` collection.

### `GET /api/v1/categories/{slug}` (public)
- Resolves an active category by slug; **also** paginates active products in that category (10 per page).
- Response 200 (custom envelope, not a `JsonResource`):

```json
{
  "category": { ...CategoryResource... },
  "products": { "current_page": 1, "data": [<Product>], "links": ..., "meta": ... }
}
```

`CategoryResource` shape: `id, name, slug, description, image_path, is_active, created_at, updated_at`.

## Cart (`/cart`)

Implementation: `app/Http/Controllers/Api/V1/CartController.php` + `App\Services\CartService`. **All cart routes use the `web` middleware**, so they accept session cookies and require CSRF for non-GET. Frontend sends `X-Session-ID` header to disambiguate guest carts (`resources/js/lib/api.ts`).

Identity resolution (`CartController::getCartForCurrentUser`):

1. Read `X-Session-ID` header → fallback to `cart_session_id` cookie → fallback to `session()->getId()`.
2. If `Auth::check()` (session/web auth), call `CartService::mergeGuestCartToUser($sessionId, $userId)` — merges guest items into the user cart with stock-aware clamping.
3. Otherwise, `CartService::getOrCreateCartForGuest($sessionId)` (auto-expires after 7 days).

### `GET /api/v1/cart`
- Loads `items.product`. Response 200:
  ```json
  {
    "success": true,
    "message": "Carrito obtenido correctamente",
    "data": <CartResource>
  }
  ```

### `POST /api/v1/cart/items`
- Body (validated by `AddCartItemRequest`): `{ "product_id": int, "quantity": int }`.
- 422 on stock issues, missing/inactive product, quantity ≤ 0.
- Response 201 (note: 201 even when updating existing line, because `addProductToCart` may either insert or increment): `{ success, message, data: <CartResource> }`.

### `PUT /api/v1/cart/items/{cart_item_id}`
- Body (`UpdateCartItemRequest`): `{ "quantity": int }`.
- Authorization: returns 403 if `cartItem.cart_id` does not belong to the resolved cart for the current request.
- 422 on insufficient stock or quantity ≤ 0.
- Response 200: `<CartResource>` snapshot.

### `DELETE /api/v1/cart/items/{cart_item_id}`
- Authorization: 403 if not owner.
- Response 200: `<CartResource>` snapshot.

### `DELETE /api/v1/cart`
- Empties the resolved cart.
- Response 200: `<CartResource>` (empty items, totals 0).

`CartResource` shape (`App\Http\Resources\Api\V1\CartResource`):

```json
{
  "id": 7, "user_id": null, "session_id": "guest_xxx",
  "total_items": 3, "total_amount": "12500.00",
  "expires_at": "2026-05-14T18:23:00Z",
  "is_expired": false,
  "items": [<CartItemResource>],
  "summary": {
    "items_count": 2, "unique_products": 2, "total_quantity": 3,
    "subtotal": "12500.00", "tax": "0.00", "total": "12500.00"
  },
  "created_at": "...", "updated_at": "..."
}
```

`CartItemResource` shape:

```json
{
  "id": 11, "cart_id": 7, "product_id": 23,
  "quantity": 2, "unit_price": "4999.99", "total_price": "9999.98",
  "product": { "id": 23, "name": "...", "slug": "...", "description": "...",
               "price": "4999.99", "stock_quantity": 5, "is_active": true,
               "image_url": "...", "category": { ... } },
  "subtotal": "9999.98",
  "added_at": "...", "updated_at": "..."
}
```

## Orders (`/orders`)

Implementation: `app/Http/Controllers/Api/V1/OrderController.php` + `OrderService`.

### `POST /api/v1/orders` (web middleware; supports both authenticated & guest)
Form request: `StoreOrderRequest` (rules branch on auth state).

Authenticated body:

```json
{
  "payment_method": "paypal",
  "shipping_address_id": 12,
  "billing_address_id": 13,           // optional, defaults to shipping
  "shipping_cost": 0.00,              // optional, ≥0 ≤9999.99
  "shipping_method_name": "Estándar", // optional
  "notes": "..."                      // optional
}
```

Guest body:

```json
{
  "payment_method": "paypal",
  "guest_email": "guest@example.com",
  "shipping_address": { "first_name", "last_name", "address_line_1", "city", "state", "postal_code", "country", ... },
  "billing_address":  { /* same fields, all required */ },
  "shipping_cost": 0.00,
  "shipping_method_name": "...",
  "notes": "..."
}
```

Flow (transactional):

1. Resolve current cart (sanctum or web user → user cart; otherwise guest cart by session id).
2. Reject 422 if cart is empty.
3. Validate stock; reject 422 with `errors.stock = [{item_id, product_name, requested_quantity, available_stock}, ...]` if insufficient.
4. Authenticated: pass `shipping_address_id`/`billing_address_id` to `OrderService::createOrderFromCart`. Guest: create temporary `addresses` rows with `user_id = null` first.
5. **Process payment** via `OrderController::processPayment` → either `PayPalPaymentService::simulateSuccessfulPayment` (when `APP_ENV=testing` or `services.paypal.simulate_payments=true`) or `processPayment` (real PayPal v2 REST: createOrder + captureOrder + updatePaymentStatus). On failure, the order is auto-cancelled (stock restored).
6. On success, mark `payment_status=pagado` (which triggers `OrderConfirmationMail`), clear the cart, and return:

```json
{
  "success": true,
  "message": "Pedido creado exitosamente.",
  "data": {
    "order": <OrderResource>,
    "payment": { "status": "success", "payment_id": "...", "gateway": "paypal" }
  }
}
```

Errors: 422 (validation, empty cart, stock, payment failure), 500 (unexpected).

### `GET /api/v1/user/orders` (sanctum)

Implementation: `app/Http/Controllers/Api/V1/User/OrderController.php`.

Query: `per_page` (default 15). Response 200:

```json
{
  "success": true,
  "data": {
    "orders": [<OrderResource>],
    "pagination": { "current_page", "per_page", "total", "last_page", "from", "to" }
  }
}
```

### `GET /api/v1/user/orders/{order_number}` (sanctum)

- 404 if not found, 403 if `order.user_id !== Auth::id()`.
- Response 200: `{ success, data: { order: <OrderResource> } }`.

`OrderResource` shape (`App\Http\Resources\Api\V1\OrderResource`):

```json
{
  "id": 1, "order_number": "CM-2026-AB12CD34",
  "status": "pendiente_pago", "payment_status": "pendiente",
  "subtotal_amount": "9999.98", "shipping_cost": "0.00", "total_amount": "9999.98",
  "payment_gateway": "paypal", "payment_id": "CAPTURE_...",
  "shipping_method_name": "...", "notes": "...",
  "created_at": "...", "updated_at": "...",
  "user": { "id", "name", "email" } /* whenLoaded */,
  "guest_email": "..." /* only when no user */,
  "shipping_address": <AddressResource> /* whenLoaded */,
  "billing_address": <AddressResource|null> /* whenLoaded */,
  "order_items": [<OrderItemResource>] /* whenLoaded */,
  "can_be_cancelled": true,
  "status_label": "Pendiente de pago",
  "payment_status_label": "Pendiente"
}
```

`OrderItemResource`: `id, product_id, product_name, quantity, price_per_unit, total_price, created_at, updated_at, product?` (nested `ProductResource` whenLoaded).

## Payments (`/payments/paypal/*`) — public (no auth middleware)

Implementation: `app/Http/Controllers/Api/V1/PaymentController.php` + `PayPalPaymentService`.

> **Security note:** these routes have no auth middleware. They validate `order_id` exists and is in `payment_status === 'pendiente'`, but they don't check ownership. Combined with the `/orders/{number}` page that exposes the order id, this means anyone who knows an order id can attempt PayPal interactions. Worth tightening before going live.

### `POST /api/v1/payments/paypal/create-order`
- Body: `{ "order_id": int }` (must exist, must be in `payment_status=pendiente`).
- Calls PayPal `POST /v2/checkout/orders` with currency `MXN` and item breakdown.
- Response 200: `{ success, data: { paypal_order_id, approval_url, order_number } }`.
- 400 on invalid state or PayPal error, 422 on validation, 500 on unexpected.

### `POST /api/v1/payments/paypal/capture-order`
- Body: `{ "order_id", "paypal_order_id" }`.
- Captures via `POST /v2/checkout/orders/{id}/capture`. On success calls `OrderService::updatePaymentStatus($id, PAID, capture_id, 'paypal')`.
- Response 200: `{ success, data: { capture_id, status, order_number, payment_status: "pagado" } }`.

### `POST /api/v1/payments/paypal/simulate-success` and `simulate-failure`
- Testing helpers. They update `payment_status` directly via `OrderService::updatePaymentStatus`.
- The `/orders` flow uses these automatically when `APP_ENV=testing` or `PAYPAL_SIMULATE_PAYMENTS=true`.

### `GET /api/v1/payments/paypal/verify-config`
- Reflectively calls `getAccessToken` to confirm credentials work.
- Response 200: `{ success, config: { mode, client_id_configured, client_secret_configured, access_token_test, access_token_length? } }`.

PayPal returns (web routes, not API): `GET /orders/payment/success`, `GET /orders/payment/cancel` — handled by `App\Http\Controllers\PaymentReturnController`, render Inertia `Payment/Success` / `Payment/Cancel` (note: those page components are referenced but not present under `resources/js/pages/Payment/` — they need to be created or the controller should redirect elsewhere).

## User addresses — `routes/web.php` (session auth!)

Implementation: `app/Http/Controllers/Api/V1/User/AddressController.php` (uses `App\Http\Resources\AddressResource` — the non-`Api/V1` one).

Despite the path looking like an API route, these are **declared inside `routes/web.php` under the `auth` + `verified` middleware group** (so they require session login, not Sanctum bearer). The frontend `AddressAPI` (`resources/js/lib/address-api.ts`) warms `GET /sanctum/csrf-cookie` once before write operations.

| Method | Path | Form Request | Notes |
| --- | --- | --- | --- |
| `GET` | `/api/v1/user/addresses` | — | Optional `?type=shipping|billing`. Returns ordered by `is_default desc, created_at desc`. |
| `POST` | `/api/v1/user/addresses` | `StoreAddressRequest` | Forces `user_id` to authenticated user. Returns `AddressResource`. |
| `GET` | `/api/v1/user/addresses/{address}` | — | 403 if not owner. |
| `PUT` / `PATCH` | `/api/v1/user/addresses/{address}` | `UpdateAddressRequest` | 403 if not owner. |
| `DELETE` | `/api/v1/user/addresses/{address}` | — | 403 if not owner. Returns `{ message: "..." }` (200). |
| `PATCH` | `/api/v1/user/addresses/{address}/set-default` | — | Sets `is_default=true`; model `boot::updating` clears siblings of same `(user_id, type)`. |

`AddressResource` shape (web variant): `id, type, first_name, last_name, full_name, company, address_line_1..2, city, state, postal_code, country, phone, is_default, full_address, created_at, updated_at`.

## Admin (`/admin/*`) — `auth:sanctum` + `admin` middleware

The `admin` middleware alias points to `EnsureUserIsAdmin` (returns `403 { "message": "Forbidden. User is not an administrator." }` if `$user->is_admin !== true`).

### Categories (`apiResource categories`)

`App\Http\Controllers\Api\V1\Admin\CategoryController`. Standard REST:

| Method | Path | Notes |
| --- | --- | --- |
| `GET` | `/api/v1/admin/categories` | Paginated 10/page. |
| `POST` | `/api/v1/admin/categories` | `StoreCategoryRequest`. |
| `GET` | `/api/v1/admin/categories/{category}` | |
| `PUT` / `PATCH` | `/api/v1/admin/categories/{category}` | `UpdateCategoryRequest`. |
| `DELETE` | `/api/v1/admin/categories/{category}` | Hard delete. There is commented code that would block deletion when products are associated — currently it does not. |

### Products (`apiResource products`)

`App\Http\Controllers\Api\V1\Admin\ProductController`. Uses `App\Http\Resources\Api\V1\Admin\ProductResource` (slightly less than the public one — no `category` relation by default).

`StoreProductRequest` validation:

```php
'category_id'    => 'required|exists:categories,id',
'name'           => 'required|string|max:255',
'slug'           => 'sometimes|string|max:255|unique:products,slug',
'description'    => 'nullable|string',
'sku'            => 'required|string|max:255|unique:products,sku',
'price'          => 'required|numeric|min:0',
'stock_quantity' => 'required|integer|min:0',
'brand'          => 'nullable|string|max:255',
'movement_type'  => 'nullable|string|max:255',
'image_path'     => 'nullable|string|max:255',
'is_active'      => 'sometimes|boolean',
```

`prepareForValidation` auto-fills `slug` from `name` via `Str::slug` when not provided.

### Image uploads

`POST /api/v1/admin/images/upload` — `App\Http\Controllers\Api\V1\Admin\ImageUploadController`.

- Body: `multipart/form-data`
  - `image` (required, file: jpeg/png/jpg/gif/svg, ≤2 MB)
  - `type` (optional, `products` or `categories`) — used as subdirectory
- Stores via `$file->storeAs($type, "<uuid>.<ext>", 'public')`.
- Response 201: `{ message, path: "products/<uuid>.jpg", url: "/storage/products/<uuid>.jpg", relative_url: "products/<uuid>.jpg" }`.
- Frontend should send `path` back in the `image_path` field of `Product`/`Category` create/update.

## AJAX endpoints — `routes/web.php` (session auth)

Used by the React `User/UserOrdersPage` and `UserOrderDetailPage` components when running inside an Inertia/web context (they rely on session cookies, not Sanctum tokens).

| Method | Path | Description |
| --- | --- | --- |
| `GET` | `/ajax/user/orders` | Same shape as `GET /api/v1/user/orders` (paginated). |
| `GET` | `/ajax/user/orders/{order_number}` | Same shape as `GET /api/v1/user/orders/{order_number}`. |

Implemented in `app/Http/Controllers/Web/UserOrderController.php`.

## Inertia (web) routes that render React pages

Not "API contracts" per se, but useful for traceability:

| Path | Page component (resources/js/pages/) |
| --- | --- |
| `/` | `welcome.tsx` |
| `/productos` | `Products/Index.tsx` (props: products[], categories[], filters) |
| `/productos/{slug}` | `Products/Show.tsx` (props: product) |
| `/carrito` | `Cart/Index.tsx` |
| `/checkout` | `Checkout/Index.tsx` |
| `/orders/confirmation/{orderNumber}` | `Orders/Confirmation.tsx` |
| `/orders/payment/success` | `Payment/Success.tsx` *(file does not yet exist)* |
| `/orders/payment/cancel` | `Payment/Cancel.tsx` *(file does not yet exist)* |
| `/dashboard` | `dashboard.tsx` (auth+verified) |
| `/user/orders[/{orderNumber}]` | `User/UserOrdersPage.tsx`, `User/UserOrderDetailPage.tsx` (auth+verified) |
| `/settings/{profile|password|addresses}` | `settings/{profile,password,addresses}.tsx` |
| `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, `/verify-email[/{id}/{hash}]`, `/confirm-password` | `auth/*.tsx` |

## Open issues / API hygiene to-dos

1. **Address routes live in `web.php`** with the Inertia/auth middleware — intentional today (works with session-based SPA), but should be documented anywhere a third-party consumer would expect Sanctum.
2. **Public payment endpoints** — `PaymentController` does not enforce ownership of `order_id`. Add `auth:sanctum` + ownership check before live launch.
3. **Inconsistent `category` query parameter** — `routes/api.php` + `ProductController` use `category` (slug); `docs/api/specifications.md` calls it `category_slug`. Code wins; spec needs an update.
4. **`HU2.6` cart merge** — guest items that exceed available stock at merge time are silently dropped/clamped (`mergeGuestCartToUser`). Surface this to the UI.
5. **`movement_type`** values inconsistent between `ProductFactory` (English) and `ProductSeeder` (Spanish). Standardize on Spanish.
6. **`Payment/Success`/`Payment/Cancel` pages** are referenced from `PaymentReturnController` but not present under `resources/js/pages/Payment/`. Either implement or redirect.
