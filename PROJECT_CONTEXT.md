## 1. Resumen del Proyecto

* **Nombre del Proyecto:** CronosMatic
* **Descripción:** Sistema de E-commerce MVP (Producto Mínimo Viable) especializado en la venta de relojes a consumidores finales (B2C).
* **Objetivo Principal del MVP:** Implementar un flujo de compra-venta completo, intuitivo y eficiente, incluyendo un panel de administración básico para la gestión de productos y categorías.
* **Base del Proyecto:** Se utiliza un starter kit de Laravel + React (con Inertia.js para autenticación/dashboard y Tailwind CSS/Shadcn UI).
* **Enfoque E-commerce:** Las funcionalidades de e-commerce se implementarán principalmente como una API RESTful pura consumida por el frontend React, separada de las rutas Inertia existentes.

## 2. Stack Tecnológico Principal

* **Backend:** Laravel (PHP 8.2+)
* **Frontend:** React (TypeScript, Vite)
* **Base de Datos:** MySQL
* **Autenticación API:** Laravel Sanctum (para tokens SPA)
* **Autenticación Web (Starter Kit):** Basada en sesión (para rutas Inertia como `/login`, `/dashboard` del starter kit)
* **Estilos:** Tailwind CSS
* **Componentes UI (Frontend):** Shadcn/UI (ver `resources/js/components/ui`)
* **Servidor de Desarrollo Local:** Laravel Sail (Docker)
* **Versionado API:** `/api/v1/`
* **Formato de Datos API:** JSON

## 3. Estructura de Directorios Clave

### Backend (Laravel)

* `app/Http/Controllers/Api/V1/`: Controladores para la API RESTful de e-commerce.
    * `Admin/`: Subdirectorio para controladores de administración (ej. `Admin/ProductController.php`).
* `app/Http/Requests/Api/V1/`: Form Requests para validación de datos de la API.
* `app/Http/Resources/Api/V1/`: API Resources para transformar modelos Eloquent a JSON.
* `app/Models/`: Modelos Eloquent (User, Product, Category, Order, OrderItem, Cart, CartItem, Address).
* `app/Services/`: Clases de servicio para lógica de negocio compleja (ej. `CartService`, `OrderService`).
* `app/Policies/`: Policies para autorización en modelos (si es necesario).
* `app/Http/Middleware/`: Middlewares personalizados (ej. `EnsureUserIsAdmin.php`).
* `database/migrations/`: Migraciones de base deatos.
* `database/factories/`: Factories para generar datos de prueba.
* `database/seeders/`: Seeders para poblar la base de datos.
* `routes/api.php`: Definición de rutas para `/api/v1/`. Usar grupos de rutas y el middleware `auth:sanctum` según sea necesario.
* `routes/web.php`: Rutas existentes del starter kit (Inertia) para auth, dashboard, etc.
* `config/`: Archivos de configuración (ej. `config/sanctum.php`, `config/cors.php`, `config/services.php` para pasarelas).
* `tests/Feature/Api/V1/`: Pruebas de API para los nuevos endpoints.
* `tests/Unit/`: Pruebas unitarias para modelos y servicios.

### Frontend (React - TypeScript)

* `resources/js/`: Directorio raíz para el código React.
* `resources/js/pages/`: Componentes de página de nivel superior (consumidos por Inertia o React Router si se usa para sub-rutas dentro de una página Inertia).
    * `Admin/`: Subdirectorio para páginas del panel de administración.
    * `Auth/`: Páginas de autenticación del starter kit (mantener si se usa Inertia para estas).
    * `Ecommerce/` o directamente: `Products/IndexPage.tsx`, `Products/ShowPage.tsx`, `CartPage.tsx`, `CheckoutPage.tsx`, `User/OrdersPage.tsx`.
* `resources/js/components/`: Componentes React reutilizables.
    * `ecommerce/`: Componentes específicos para e-commerce (ej. `ProductCard.tsx`).
    * `admin/`: Componentes específicos para el panel de administración.
    * `ui/`: Componentes UI de Shadcn (ya existentes).
    * `layouts/`: Layouts principales de la aplicación (ej. `AppLayout.tsx`, `AdminLayout.tsx`).
* `resources/js/services/` o `resources/js/api/`: Módulos para encapsular llamadas a la API de Laravel (ej. `productService.ts`, `authService.ts`).
* `resources/js/hooks/`: Custom Hooks de React (ej. `useCart.ts`).
* `resources/js/types/`: Definiciones TypeScript para entidades y payloads de API.
* `resources/js/store/` o `resources/js/contexts/`: Para gestión de estado global si se necesita (ej. Context API para el carrito).

## 4. Convenciones de Código y Estilo

### General

* Seguir las reglas definidas en `.editorconfig`, `.eslintrc.js` (o `eslint.config.js`), y `.prettierrc`.
* Comentarios en español cuando sea posible para el código del proyecto. Documentación y mensajes de commit pueden ser en inglés o español según se prefiera.

### Backend (PHP - Laravel)

* **Estilo:** PSR-12. Usar `php artisan pint` para formatear.
* **Nombres:**
    * Controladores: `NombreSingularController.php` (ej. `ProductController`).
    * Modelos: `NombreSingular.php` (ej. `Product`).
    * Migrations: `timestamp_create_nombre_plural_table.php`.
    * Rutas API: Recursos en plural, en inglés (ej. `/api/v1/products`).
    * Métodos de controlador API: `index`, `store`, `show`, `update`, `destroy`.
* **Prácticas:**
    * Usar Form Requests para la validación en controladores.
    * Usar API Resources para formatear las respuestas JSON.
    * Preferir el uso de Clases de Servicio para encapsular lógica de negocio compleja fuera de los controladores.
    * Usar inyección de dependencias.
    * Seguir las convenciones de Laravel para relaciones Eloquent, scopes, etc.

### Frontend (TypeScript - React)

* **Nombres de Componentes:** `PascalCase` (ej. `ProductCard.tsx`).
* **Nombres de Archivos:** `kebab-case` o `PascalCase` para componentes `.tsx` (el starter kit usa `kebab-case` para algunos componentes UI y `PascalCase` para páginas, seguir consistencia o definir una). Para este proyecto, usaremos `PascalCase.tsx` para componentes y páginas React.
* **Props:** Usar interfaces TypeScript para definir las props.
* **Estilo:** Seguir las reglas de ESLint y Prettier. Usar `npm run lint` y `npm run format`.
* **Componentes Funcionales y Hooks:** Preferir componentes funcionales y el uso de hooks.
* **Estilos CSS:** Usar clases de Tailwind CSS directamente en el JSX. Utilizar `@apply` en `resources/css/app.css` con moderación. Aprovechar los componentes de Shadcn/UI.

### API (General)

* **URL Base:** `/api/v1/`
* **Formato:** `JSON` para request y response. Claves en `snake_case`.
* **Métodos HTTP:** Usar los verbos correctos (GET, POST, PUT, DELETE, PATCH).
* **Autenticación:** Laravel Sanctum con Bearer Tokens para rutas protegidas.
* **Respuestas de Error:** Utilizar los códigos de estado HTTP apropiados y el formato de error JSON definido.
* **Rutas:** En inglés, en plural.

## 5. Instrucciones Específicas para la IA (Cursor)

### Creación de Componentes/Archivos Backend (Laravel)

* **Modelos:** Crear en `app/Models/`. Incluir `$fillable`, `$casts`, `$hidden` (si aplica), y relaciones Eloquent. Usar `php artisan make:model NombreModelo -mfs` (para migración, factory, seeder).
* **Migrations:** Crear en `database/migrations/`. Definir esquema con tipos de datos MySQL. Usar `Schema::create(...)`.
* **Factories:** Crear en `database/factories/`. Usar Faker para datos de prueba.
* **Seeders:** Crear en `database/seeders/`. Llamar a factories. Actualizar `DatabaseSeeder.php`.
* **Controladores API:** Crear en `app/Http/Controllers/Api/V1/` (o `App/Http/Controllers/Api/V1/Admin/`). Usar `php artisan make:controller Api/V1/NombreController --api`.
* **Form Requests:** Crear en `app/Http/Requests/Api/V1/`. Usar `php artisan make:request Api/V1/NombreRequest`.
* **API Resources:** Crear en `app/Http/Resources/Api/V1/`. Usar `php artisan make:resource Api/V1/NombreResource`.
* **Rutas API:** Añadir a `routes/api.php` dentro del grupo `/v1`. Proteger con `middleware(['auth:sanctum'])` y `middleware('admin')` (middleware personalizado `EnsureUserIsAdmin`) según sea necesario.
* **Servicios:** Crear en `app/Services/`. Clases PHP simples con lógica de negocio.

### Creación de Componentes/Archivos Frontend (React)

* **Componentes:** Crear en `resources/js/components/ecommerce/` o `resources/js/components/admin/`. Archivos `.tsx` con nombre en `PascalCase`.
* **Páginas:** Crear en `resources/js/pages/` (o subdirectorios). Archivos `.tsx` en `PascalCase`.
* **Servicios API (Frontend):** Crear en `resources/js/services/` (ej. `productService.ts`). Deben usar `Workspace` o `axios` (instalar axios si se prefiere) para interactuar con la API de Laravel, manejando tokens Sanctum.
* **Hooks:** Crear en `resources/js/hooks/`. Archivos `.ts` o `.tsx` en `useCamelCase`.
* **Tipos TypeScript:** Definir en `resources/js/types/index.d.ts` o archivos específicos por módulo.

### Desarrollo de Endpoints API

* Seguir el patrón de API Resources para las respuestas.
* Implementar validación usando Form Requests.
* La autenticación debe ser manejada por el middleware `auth:sanctum`.
* Las rutas de administración deben usar adicionalmente un middleware de rol (ej. `EnsureUserIsAdmin`).

### Manejo de Estado en Frontend

* Para estado global simple (como el carrito de compras o el usuario autenticado), utilizar **React Context API** para el MVP.
* Para estado local de componentes, usar `useState` y `useReducer`.

### Pruebas

* **Backend:** Escribir Feature tests (PHPUnit) para todos los endpoints API en `tests/Feature/Api/V1/`. Escribir Unit tests (PHPUnit) para lógica compleja en Modelos o Servicios en `tests/Unit/`.
* **Frontend:** (Considerar para etapas posteriores al MVP inicial si el tiempo es limitado) Escribir tests unitarios/integración para componentes React clave usando Jest y React Testing Library.

### Internacionalización (i18n)

* **Idioma Principal UI:** Español.
* **Datos (ej. nombres de producto, descripciones):** Español.
* **Rutas API y claves JSON:** Inglés.
* No se implementará un sistema multi-idioma completo en el MVP.

### Subida de Archivos

* Utilizar el endpoint dedicado `POST /api/v1/admin/images/upload` para subir imágenes.
* Este endpoint guardará la imagen usando Laravel Filesystem (disco `public`) y devolverá la ruta relativa.
* Los controladores de Producto/Categoría asociarán esta ruta relativa al modelo correspondiente.

## 6. Qué Hacer y Qué No Hacer (Do's and Don'ts)

### Do's:

* **Seguir las convenciones de nombrado y estilo definidas.**
* **Escribir código claro, legible y comentado donde sea necesario.**
* **Desacoplar la lógica de negocio en Clases de Servicio (Laravel).**
* **Utilizar Form Requests para validación y API Resources para respuestas (Laravel).**
* **Crear componentes React pequeños y reutilizables.**
* **Utilizar TypeScript para mejorar la robustez del frontend.**
* **Escribir pruebas (especialmente feature tests para la API).**
* **Manejar errores de forma adecuada y devolver mensajes útiles desde la API.**
* **Proteger todos los endpoints que requieran autenticación/autorización.**

### Don'ts:

* **No escribir lógica de negocio directamente en las rutas o en los controladores de forma excesiva.**
* **No realizar consultas directas a la base de datos desde los controladores; usar Modelos Eloquent o Repositories (si se decide usar este patrón).**
* **No crear componentes React monolíticos y difíciles de mantener.**
* **No omitir la validación de datos (tanto en frontend como en backend).**
* **No exponer información sensible en las respuestas de la API.**
* **No hardcodear tokens o claves secretas; usar variables de entorno.**

## 7. Contexto Adicional Importante

* El proyecto se basa en un **starter kit de Laravel + React que utiliza Inertia.js**. Las funcionalidades de e-commerce deben construirse como una API RESTful separada (`/api/v1/`) que será consumida por el frontend React. Las rutas y controladores de Inertia existentes para autenticación y dashboard se mantendrán y coexistirán.
* El frontend React (Shadcn/UI, Tailwind CSS) consumirá esta nueva API para el catálogo, carrito, checkout, y el nuevo panel de administración.
* El **foco del MVP** está en el flujo de compra del cliente y en la gestión básica de productos/categorías por parte del administrador.
* La subida de imágenes para productos/categorías se gestionará mediante un endpoint API dedicado, y las imágenes se almacenarán en el disco `public` de Laravel (accesibles vía `storage` symlink).
* La gestión del carrito tendrá persistencia en backend (sesión para invitados, BD para usuarios logueados).

---