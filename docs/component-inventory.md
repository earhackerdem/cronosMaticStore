# Component Inventory — CronosMatic Store (Frontend)

> Generated: 2026-05-07 — Source of truth: `resources/js/`. All components are React 19 + TypeScript and styled with Tailwind v4.

## Mounting

`resources/js/app.tsx` bootstraps the SPA:

```tsx
createInertiaApp({
  resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
  setup({ el, App, props }) {
    createRoot(el).render(
      <CartProvider>
        <App {...props} />
        <Toaster richColors position="top-right" />
      </CartProvider>
    );
  },
});
```

Two app-wide concerns are wired here: **cart state** (`CartProvider` from `contexts/CartContext.tsx`) and **toast notifications** (`sonner`'s `Toaster`).

The Inertia "page" prop comes from the Laravel side via `App\Http\Middleware\HandleInertiaRequests::share`, which exposes:

- `auth.user` (current authenticated user, or null)
- `name` (`config('app.name')`)
- `quote` (random Inspiring quote)
- `ziggy` (route definitions for `route()` helper)
- `sidebarOpen` (cookie-driven boolean)

## Pages

`resources/js/pages/` — each `.tsx` file corresponds to an Inertia route resolution.

### Public / customer

| Page | Path | Inertia route | Notes |
| --- | --- | --- | --- |
| `welcome.tsx` | `/` | `welcome` | Landing |
| `Products/Index.tsx` | `/productos` | `web.products.index` | Catalog grid + filters (search, category, sort). Server-rendered prop: `{ products, categories, filters }` from `App\Http\Controllers\ProductController::index`. |
| `Products/Show.tsx` | `/productos/{slug}` | `web.products.show` | Detail page. Eager-loads category. Uses `add-to-cart-button`. |
| `Cart/Index.tsx` | `/carrito` | `web.cart.index` | Reads `useCart()` to render items, totals, edit qty, remove. |
| `Checkout/Index.tsx` | `/checkout` | `web.checkout.index` | Address selection (via `useAddresses`), payment trigger (PayPal). |
| `Orders/Confirmation.tsx` | `/orders/confirmation/{orderNumber}` | `web.orders.confirmation` | Post-purchase confirmation. Receives `orderNumber` prop. |

### Authenticated user

| Page | Path | Notes |
| --- | --- | --- |
| `dashboard.tsx` | `/dashboard` | Starter-kit dashboard. |
| `User/UserOrdersPage.tsx` | `/user/orders` | Order history (paginated). Uses `UserOrderApi` (`/ajax/user/orders`). |
| `User/UserOrderDetailPage.tsx` | `/user/orders/{orderNumber}` | Detail with items + addresses. |
| `settings/profile.tsx` | `/settings/profile` | Update name/email. |
| `settings/password.tsx` | `/settings/password` | Change password. |
| `settings/addresses.tsx` | `/settings/addresses` | Address book CRUD. Uses `useAddresses`. |

### Auth (Inertia, starter kit)

`resources/js/pages/auth/`: `login.tsx`, `register.tsx`, `forgot-password.tsx`, `reset-password.tsx`, `verify-email.tsx`, `confirm-password.tsx`.

### Missing pages referenced by routes

`PaymentReturnController` renders `Inertia::render('Payment/Success')` and `Inertia::render('Payment/Cancel')` — those page files do **not** exist under `resources/js/pages/Payment/`. They need to be created (or the controller redirected). Tracked as an open issue in `docs/api-contracts.md`.

## Layouts (`resources/js/layouts/`)

| Layout | Purpose |
| --- | --- |
| `app-layout.tsx` | Authenticated app shell (sidebar + header). |
| `app/app-header-layout.tsx` | Header-only variant. |
| `app/app-sidebar-layout.tsx` | Sidebar variant of the app shell. |
| `auth-layout.tsx` | Wrapper for auth pages. |
| `auth/auth-card-layout.tsx`, `auth/auth-simple-layout.tsx`, `auth/auth-split-layout.tsx` | Three visual styles for auth screens. |
| `settings/layout.tsx` | Settings-section sidebar. |

## App-level components (`resources/js/components/`)

These are project-specific (not Shadcn primitives). Most are starter-kit UI scaffolding; only the e-commerce-specific items are critical to the MVP.

### E-commerce-specific

| Component | Purpose | Used by |
| --- | --- | --- |
| `add-to-cart-button.tsx` | Renders the "Añadir al carrito" CTA, reads `stock_quantity`, disables when 0, calls `useCart().addToCart`, fires a sonner toast on success/failure. | `Products/Index`, `Products/Show` |
| `cart-indicator.tsx` | Header cart icon with badge showing `cart.total_items`. Re-renders via `useCart`. | `app-header.tsx` |

### Address book

| Component | Purpose |
| --- | --- |
| `address-card.tsx` | Display one address; expose Edit / Delete / Set-default actions. |
| `address-form.tsx` | React-hook-form + Zod form for creating/updating addresses. Used in settings page and checkout. |
| `delete-address-dialog.tsx` | Confirmation modal (Radix Dialog). |

### Account

| Component | Purpose |
| --- | --- |
| `delete-user.tsx` | Settings-page delete-account confirmation. |
| `user-info.tsx`, `user-menu-content.tsx` | User dropdown contents in `app-header`. |

### App shell / navigation

`app-header.tsx`, `app-sidebar.tsx`, `app-shell.tsx`, `app-content.tsx`, `app-sidebar-header.tsx`, `app-logo.tsx`, `app-logo-icon.tsx`, `breadcrumbs.tsx`, `nav-main.tsx`, `nav-footer.tsx`, `nav-user.tsx`, `heading.tsx`, `heading-small.tsx`, `icon.tsx`, `text-link.tsx`, `input-error.tsx` — all part of the starter-kit shell. Compose them via the layouts above.

## UI primitives (`resources/js/components/ui/`)

Shadcn/UI wrappers around Radix. Don't reimplement these — compose them.

| Primitive | File | Backed by |
| --- | --- | --- |
| Alert | `alert.tsx` | — (Tailwind) |
| Avatar | `avatar.tsx` | `@radix-ui/react-avatar` |
| Badge | `badge.tsx` | — |
| Breadcrumb | `breadcrumb.tsx` | — |
| Button | `button.tsx` | `class-variance-authority` |
| Card | `card.tsx` | — |
| Checkbox | `checkbox.tsx` | `@radix-ui/react-checkbox` |
| Collapsible | `collapsible.tsx` | `@radix-ui/react-collapsible` |
| Dialog | `dialog.tsx` | `@radix-ui/react-dialog` |
| Dropdown menu | `dropdown-menu.tsx` | `@radix-ui/react-dropdown-menu` |
| Icon | `icon.tsx` | `lucide-react` |
| Input | `input.tsx` | — |
| Label | `label.tsx` | `@radix-ui/react-label` |
| Loading spinner | `loading-spinner.tsx` | — |
| Navigation menu | `navigation-menu.tsx` | `@radix-ui/react-navigation-menu` |
| Placeholder pattern | `placeholder-pattern.tsx` | — |
| Select | `select.tsx` | `@radix-ui/react-select` |
| Separator | `separator.tsx` | `@radix-ui/react-separator` |
| Sheet | `sheet.tsx` | `@radix-ui/react-dialog` (sheet variant) |
| Sidebar | `sidebar.tsx` | — |
| Skeleton | `skeleton.tsx` | — |
| Tabs | `tabs.tsx` | `@radix-ui/react-tabs` |
| Toggle | `toggle.tsx` | `@radix-ui/react-toggle` |
| Toggle group | `toggle-group.tsx` | `@radix-ui/react-toggle-group` |
| Tooltip | `tooltip.tsx` | `@radix-ui/react-tooltip` |

## Hooks (`resources/js/hooks/`)

| Hook | Purpose |
| --- | --- |
| `use-addresses.ts` | CRUD wrapper around `addressAPI`. **Skips API calls for guest users** (uses `usePage<SharedData>().props.auth.user` to detect). Surfaces toasts via `sonner`. Handles set-default state propagation client-side (clears `is_default` on siblings of same `type`). |
| `use-appearance.tsx` | Light/dark theme toggle, persists in cookie via `HandleAppearance` middleware. |
| `use-initials.tsx` | Format user initials from a name. |
| `use-mobile.tsx` / `use-mobile-navigation.ts` | Responsive helpers. |

There is no e-commerce-specific cart hook beyond `useCart()` (re-exported from `contexts/CartContext.tsx`).

## Contexts (`resources/js/contexts/`)

### `CartContext.tsx`

State machine via `useReducer`:

```ts
type CartAction =
  | { type: 'SET_LOADING'; payload: boolean }
  | { type: 'SET_CART'; payload: Cart | null }
  | { type: 'SET_ERROR'; payload: string | null }
  | { type: 'CLEAR_ERROR' };
```

Public `useCart()` API:

```ts
{
  cart: Cart | null;
  isLoading: boolean;
  error: string | null;
  addToCart(productId: number, quantity?: number): Promise<void>;
  updateCartItem(itemId: number, quantity: number): Promise<void>;
  removeCartItem(itemId: number): Promise<void>;
  clearCart(): Promise<void>;
  refreshCart(): Promise<void>;
}
```

`CartProvider` calls `refreshCart()` on mount via `useEffect(() => refreshCart(), [])`. All state mutations go through `CartApi` (see below) and re-set the full `Cart` snapshot from the server.

There is **no Redux/Zustand**. If you need additional global state, follow the same context pattern.

## API client modules (`resources/js/lib/`)

### `axios.ts` — global axios setup

- Sets `X-Requested-With: XMLHttpRequest`, `withCredentials: true`.
- Reads CSRF token from `<meta name="csrf-token">`.
- Interceptors:
  - **Request**: appends `Authorization: Bearer ${localStorage.auth_token}` when present.
  - **Response**: on 401, clears `auth_token` and redirects to `/login` (unless already on a login page).

### `api.ts`

- `CartApi` — *uses fetch directly* (not axios). Always sends `X-Session-ID` header. Stores guest session id in `localStorage.cart_session_id`. Public methods: `getCart`, `addToCart`, `updateCartItem`, `removeCartItem`, `clearCart`, plus `clearStoredSessionId` and `setSessionId` (test helper).
- `UserOrderApi` — fetch-based wrapper around `/ajax/user/orders[/{number}]`; redirects to `/login` on 401.

### `address-api.ts`

- Uses an axios instance configured for **session/web auth** (cookies + CSRF token). Initializes the Sanctum CSRF cookie via `GET /sanctum/csrf-cookie` once before the first write.
- Class `AddressAPI` exports `addressAPI` singleton with `getAddresses`, `getAddress`, `createAddress`, `updateAddress`, `deleteAddress`, `setAsDefault`.

### `utils.ts`

`cn()` helper — tiny wrapper around `clsx` + `tailwind-merge`.

## TypeScript types (`resources/js/types/`)

`index.d.ts` exports the canonical interfaces shared with the backend payloads:

`User, Auth, BreadcrumbItem, NavItem, NavGroup, SharedData (Inertia shared props), Category, Product, PaginatedResponse<T>, ProductsIndexProps, CartItem, Cart, CartContextType, Address, OrderItem, Order, OrdersPaginatedResponse`.

These are the shapes returned by `App\Http\Resources\Api\V1\*` resources. Keep them in sync when API responses change.

## Tests (`resources/js/__tests__/`)

| Path | Contents |
| --- | --- |
| `__tests__/components/` | `AddToCartButton.test.tsx`, `AddressCard.test.tsx`, `AddressForm.test.tsx`, `CartIndicator.test.tsx`, `DeleteAddressDialog.test.tsx`, `ui/button.test.tsx`, `ui/loading-spinner.test.tsx` |
| `__tests__/pages/` | `Cart/Index.test.tsx`, `Checkout/Index.test.tsx`, `Products/Index.test.tsx`, `Products/Show.test.tsx` |
| `__tests__/hooks/` | `useAddresses.test.ts` |
| `__tests__/lib/` | `address-api.test.ts` |
| `__tests__/utils/` | `test-utils.tsx` (custom render with providers) |

E2E tests live separately in `cypress/e2e/`: `address-management.cy.ts`, `cart-functionality.cy.ts`, `checkout/`, `products.cy.ts`.

## Design system notes

- **Tailwind v4** with `@tailwindcss/vite` plugin. Default theme — extend in `resources/css/app.css` or via Tailwind config.
- **Forms**: standardize on `react-hook-form` + `@hookform/resolvers` + `zod`. `address-form.tsx` is a reference implementation.
- **Toasts**: only via `sonner`'s `toast.*` API; no other notification library.
- **Icons**: `lucide-react` (no FontAwesome).
- **Class merging**: use `cn()` from `lib/utils.ts`, never manual string concatenation.
- **Variants**: use `class-variance-authority` (already a dep) for components with variant matrices (see `ui/button.tsx`).
