## 2. Arquitectura del Sistema

### a. Diagrama de Arquitectura (Mermaid)

```mermaid
graph TD
    subgraph Cliente
        Frontend["React SPA: Single Page Application"]
    end

    subgraph Servidor
        Backend["Laravel API: API RESTful"]
        subgraph "Base de Datos"
            DB[(MySQL)]
        end
    end

    subgraph "Servicios de Terceros"
        PaymentGateway["Pasarela de Pago: ej. PayPal Sandbox"]
        MailService["Servicio de Correo Transaccional: ej. SMTP Laravel / Mailgun"]
    end

    Frontend -- HTTPS (JSON/API Calls) --> Backend
    Backend -- CRUD (SQL) --> DB
    Backend -- API Seguro --> PaymentGateway
    Backend -- SMTP/API --> MailService
    MailService -- Emails --> UsuarioFinal((Usuario Final))
    UsuarioFinal -- Interacción --> Frontend

    style Frontend fill:#D6EAF8,stroke:#3498DB,stroke-width:2px
    style Backend fill:#D5F5E3,stroke:#2ECC71,stroke-width:2px
    style DB fill:#FCF3CF,stroke:#F1C40F,stroke-width:2px
    style PaymentGateway fill:#FDEDEC,stroke:#E74C3C,stroke-width:2px
    style MailService fill:#FDEBD0,stroke:#E67E22,stroke-width:2px
    style UsuarioFinal fill:#E8DAEF,stroke:#8E44AD,stroke-width:2px
````

### b. Descripción de los Componentes Principales 

  * **Cliente (Frontend - React SPA):**

      * **Tecnología:** React.js, TypeScript, Vite, Shadcn/UI (inferido de la estructura del starter kit ).
      * **Responsabilidades MVP:**
          * Renderizar la interfaz de usuario (páginas de listado de productos, detalle de producto, carrito, checkout, login, registro, perfil de usuario básico).
          * Manejar interacciones del usuario.
          * **Para funcionalidades de e-commerce (catálogo, carrito, checkout), React consumirá directamente la API RESTful de Laravel mediante peticiones asíncronas (fetch/axios). Inertia se seguirá utilizando para la carga inicial de las 'páginas' y para las secciones existentes (autenticación, dashboard) que ya provee el starter kit.**
          * Gestión de estado local/contextual (ej. para el carrito, con sincronización al backend).
          * Validaciones básicas del lado del cliente.

  * **Servidor (Backend API - Laravel):**

      * **Tecnología:** Laravel Framework (PHP).
      * **Responsabilidades MVP:**
          * **API RESTful:**
              * **Las rutas para las funcionalidades de e-commerce se definirán como API RESTful en `routes/api.php` (ej. `/api/v1/products`, `/api/v1/cart`, `/api/v1/orders`).**
              * Las rutas existentes en `routes/web.php` se mantendrán para el flujo de autenticación y dashboard gestionado por Inertia.
          * **Lógica de Negocio:**
              * Gestión de Catálogo: CRUD básico para productos (relojes).
              * **Gestión de Carrito:** Lógica para añadir, actualizar, ver ítems. **El carrito tendrá persistencia básica en el backend: se utilizará la sesión para carritos de invitados y una tabla en la base de datos (`carts`, `cart_items`) para usuarios autenticados, asegurando que el carrito persista entre sesiones para usuarios logueados.**
              * Gestión de Pedidos: Crear pedidos, actualizar estados básicos.
              * Procesamiento de Pagos: Integración con la pasarela de pago (PayPal Sandbox).
          * **Autenticación:**
              * La autenticación existente (probablemente Laravel Breeze/Jetstream vía Inertia) se mantendrá para las rutas web.
              * **La autenticación para los endpoints de la API de e-commerce (`/api/v1/*`) se gestionará mediante Laravel Sanctum (autenticación basada en tokens para SPA).**
          * **Interacción con BD:** Uso de Eloquent ORM para interactuar con MySQL.
          * **Notificaciones:** Envío de correos transaccionales básicos (confirmación de pedido) usando el sistema de Mail de Laravel.

  * **Persistencia (Base de Datos - MySQL):**

      * **Tecnología:** MySQL.
      * **Responsabilidades:** Almacenamiento persistente de:
          * Usuarios (`users` table).
          * Productos (`products`).
          * Categorías (`categories`).
          * **Carritos (`carts`) y sus ítems (`cart_items`) para persistencia de usuarios logueados y potencialmente invitados (asociados a sesión).**
          * Pedidos (`orders`) y sus ítems (`order_items`).
          * Direcciones (`addresses`) asociadas a usuarios o pedidos.
      * Migraciones y factories para definir y sembrar el esquema.

  * **Pasarela de Pago (PayPal Sandbox u otra con Sandbox):**

      * **Responsabilidad:** Procesar pagos de forma segura en MXN.
      * **Integración:** El backend Laravel se comunicará con la API de la pasarela.

  * **Servicio de Correo Transaccional:**

      * **Responsabilidad:** Envío de correos electrónicos (confirmación de pedido, registro, etc.).
      * **Integración:** Laravel Mail.

### c. Descripción de Alto Nivel del Proyecto y Estructura de Ficheros 

El proyecto CronosMatic es una aplicación web de comercio electrónico con una arquitectura desacoplada:

  * **Backend (Laravel):**

      * `app/Http/Controllers/Api/V1/`: Controladores específicos para la API de e-commerce (ej. `ProductController`, `CartController`, `OrderController`).
      * `app/Http/Middleware/`: **Posiblemente middlewares específicos para la API, como `EnsureFrontendRequestsAreStateful` de Sanctum si se usa la opción de cookies, o para verificar tokens.**
      * `app/Models/`: Modelos Eloquent para `Product`, `Category`, `Order`, `OrderItem`, `Cart`, `CartItem`, `Address`.
      * `app/Services/`: Para lógica de negocio (ej. `CartService`, `OrderProcessingService`).
      * `app/Http/Requests/Api/V1/`: Form Requests para la API.
      * `app/Http/Resources/Api/V1/`: API Resources para transformar modelos a JSON.
      * `database/migrations/`: Migraciones para `products`, `categories`, `carts`, `cart_items`, `orders`, `order_items`, `addresses`.
      * `routes/api.php`: **Definirá todos los endpoints RESTful para el e-commerce (ej. `/api/v1/products`, `/api/v1/cart`, `/api/v1/orders`, `/api/v1/checkout`). Estos endpoints estarán protegidos por Sanctum según sea necesario.**
      * `routes/web.php`: Mantendrá las rutas para autenticación, dashboard y otras páginas gestionadas por Inertia.

  * **Frontend (React):**

      * `resources/js/pages/`: Páginas para `Products/IndexPage.tsx`, `Products/ShowPage.tsx`, `CartPage.tsx`, `CheckoutPage.tsx`, `User/OrdersPage.tsx`.
      * `resources/js/components/ecommerce/`: Componentes específicos del e-commerce (ej. `ProductCard.tsx`, `CartView.tsx`).
      * `resources/js/services/` o `resources/js/api/`: **Módulos para encapsular las llamadas a la API RESTful de Laravel (ej. `productService.ts`, `cartService.ts`, `orderService.ts`). Estos servicios utilizarán fetch/axios configurados para enviar el token de Sanctum si es necesario.**
      * `resources/js/hooks/`: **Hooks como `useCart.ts` que interactuará con el `cartService` para la sincronización con el backend.**
      * **Enrutamiento:** **La carga inicial de las "páginas" principales del e-commerce (ej. `/products`, `/cart`) puede seguir siendo manejada por Inertia. Dentro de estas páginas, los componentes React realizarán llamadas directas a la API RESTful de Laravel para obtener y manipular los datos específicos del e-commerce.** Ziggy seguirá siendo útil para generar URLs tanto para rutas Inertia como para rutas API.

### d. Infraestructura y Despliegue (Consideraciones MVP)

  * **Desarrollo Local:** Laravel Sail (Docker), PHP, Node.js, MySQL. El starter kit ya incluye Laravel Sail.
  * **Servidor MVP:**
      * Un VPS básico (ej. DigitalOcean, Linode) o una PaaS (ej. Heroku, Laravel Forge).
      * Software: Servidor web (Nginx recomendado), PHP (compatible con Laravel actual), MySQL, Node.js (para el build del frontend).
  * **Base de Datos:** MySQL.
  * **Dominio y DNS:** Necesario para acceso público y SSL.
  * **SSL:** Certificado SSL (ej. Let's Encrypt) es imprescindible para HTTPS.
  * **Proceso de Despliegue MVP:** Manual (FTP/SCP, git pull) o vía Laravel Forge. Assets de frontend compilados con `npm run build` y subidos.

### e. Seguridad (Consideraciones MVP)

  * **Laravel (Backend):**
      * Protección CSRF (para rutas web con Inertia).
      * Protección XSS (Eloquent y React/Inertia ayudan por defecto).
      * Validación de entradas en `FormRequests` o controladores.
      * Manejo seguro de contraseñas (hashing por defecto).
      * **Laravel Sanctum:** Para autenticación de API RESTful (`/api/v1/*`) usando tokens Bearer.
      * Configuración adecuada de CORS (`config/cors.php`) si fuera necesario.
  * **React (Frontend):**
      * Evitar `dangerouslySetInnerHTML` sin sanitización.
  * **General:**
      * **HTTPS Obligatorio.**
      * Integración segura con Pasarela de Pago (no almacenar datos sensibles de tarjetas).
      * Mantener dependencias actualizadas (Composer y NPM).
      * Cabeceras de seguridad HTTP básicas.

### f. Test (Consideraciones MVP)

  * **Backend (Laravel - PHPUnit):**
      * **Unit Tests (`tests/Unit`):** Para modelos, servicios, helpers.
      * **Feature Tests (`tests/Feature`):** Para probar los endpoints de `routes/api.php` (incluyendo lógica de negocio, autenticación Sanctum y respuestas JSON) y las rutas web de Inertia existentes.
      * SQLite en memoria para tests (`phpunit.xml` ).
  * **Frontend (React):**
      * **Unit/Integration Tests (ej. Jest + React Testing Library):** Para componentes individuales y flujos de componentes. (Requiere configuración adicional, no presente en el starter kit base).
      * *(Opcional para MVP)* **E2E Tests (ej. Cypress, Playwright):** Para flujos completos de usuario.

