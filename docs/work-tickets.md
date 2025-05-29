**6. Tickets de Trabajo - CronosMatic**

**Ticket #1: Configuración Fundacional de API RESTful para E-commerce y Autenticación Sanctum**

* **ID del Ticket:** `TASK-CM-001`
* **Título:** Establecer la infraestructura base para la API RESTful v1 y configurar Laravel Sanctum para autenticación SPA.
* **Historia(s) de Usuario Relacionada(s):** Habilita indirectamente todas las HU que dependen de la API (Cliente y Admin). Específicamente necesario para HU4.2 (login vía API), HU4.1 (registro vía API), y todas las HU de Admin (HU7.x) que requerirán endpoints API protegidos.
* **Descripción:**
    1.  Instalar y configurar Laravel Sanctum para proveer autenticación basada en tokens para la Single Page Application (React). Esto incluye publicar y ejecutar migraciones de Sanctum.
    2.  Definir un nuevo grupo de rutas en `routes/api.php` con el prefijo `/v1` para todos los endpoints de e-commerce.
    3.  Asegurar que las rutas dentro de `/api/v1/` que requieran autenticación utilicen el middleware `auth:sanctum`.
    4.  Configurar `config/cors.php` para permitir peticiones desde el frontend (considerar `supports_credentials => true` y los dominios permitidos, inicialmente el mismo dominio de la app).
    5.  Ajustar el middleware `EnsureFrontendRequestsAreStateful` en `app/Http/Kernel.php` para el grupo `api` si se va a utilizar la autenticación de SPA basada en cookies de Sanctum (aunque se priorizará tokens, la configuración base es similar).
    6.  Establecer convenciones para las respuestas JSON de la API (estructura para datos exitosos y errores, como se definió en las Especificaciones de API).
    7.  Crear un controlador de prueba (ej. `Api/V1/HealthCheckController.php`) con un endpoint público (ej. `GET /api/v1/status`) y un endpoint protegido por Sanctum (ej. `GET /api/v1/auth-status`) para verificar la configuración.
* **Criterios de Aceptación:**
    * Se puede instalar y configurar Sanctum sin errores.
    * Las migraciones de Sanctum se ejecutan correctamente.
    * Un endpoint público de prueba bajo `/api/v1/` responde correctamente (ej. `GET /api/v1/status` devuelve `{"status": "ok"}`).
    * Un endpoint protegido de prueba bajo `/api/v1/` devuelve error 401 si no se provee token.
    * Un endpoint protegido de prueba bajo `/api/v1/` devuelve una respuesta exitosa (ej. 200) si se provee un token Sanctum válido (obtenido tras un login/registro vía API, a desarrollar en ticket posterior).
    * La configuración CORS permite peticiones desde el dominio del frontend.
* **Prioridad:** **Más Alta**
* **Notas Adicionales:** Este ticket es un bloqueador para la mayoría de las funcionalidades de e-commerce. Se deben seguir las Especificaciones de API definidas previamente para el manejo de errores y formato de respuesta. Considerar que el starter kit ya tiene autenticación web con Inertia; esta configuración de Sanctum es para la API que consumirá el frontend React de forma más directa para las operaciones de e-commerce.

---

**Epic: Autenticación (API y Funcionalidad de Usuario)**

* **ID del Ticket:** `TASK-CM-002`
* **Título:** API Endpoints (Sanctum) para Registro e Inicio de Sesión de Usuarios
* **Historia(s) de Usuario Relacionada(s):** HU4.1, HU4.2
* **Descripción:**
    1.  Crear `Api/V1/Auth/RegisteredUserController.php` con un método `store` para registrar nuevos usuarios. Validar datos (nombre, email único, password confirmado). Devolver el objeto usuario y un token Sanctum.
    2.  Crear `Api/V1/Auth/AuthenticatedSessionController.php` con un método `store` para iniciar sesión. Validar credenciales. Devolver el objeto usuario y un token Sanctum.
    3.  Definir las rutas `POST /api/v1/auth/register` y `POST /api/v1/auth/login` en `routes/api.php`.
* **Criterios de Aceptación:**
    * `POST /api/v1/auth/register` con datos válidos crea un usuario, devuelve 201 y un token.
    * `POST /api/v1/auth/register` con email duplicado o datos inválidos devuelve 422 con errores.
    * `POST /api/v1/auth/login` con credenciales válidas devuelve 200 y un token.
    * `POST /api/v1/auth/login` con credenciales inválidas devuelve 401 o 422.
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-003`
* **Título:** API Endpoints (Sanctum) para Logout y Obtener Usuario Autenticado
* **Historia(s) de Usuario Relacionada(s):** HU4.3, HU4.4 (parcialmente, para obtener datos)
* **Descripción:**
    1.  Modificar/crear `Api/V1/Auth/AuthenticatedSessionController.php` para incluir un método `destroy` que invalide el token Sanctum actual del usuario.
    2.  Crear un método (ej. en `Api/V1/Auth/ProfileController.php` o similar) para obtener los datos del usuario autenticado actualmente (`GET /api/v1/auth/user`).
    3.  Definir las rutas `POST /api/v1/auth/logout` (protegida por `auth:sanctum`) y `GET /api/v1/auth/user` (protegida por `auth:sanctum`).
* **Criterios de Aceptación:**
    * `POST /api/v1/auth/logout` con token válido invalida el token y devuelve 204.
    * `GET /api/v1/auth/user` con token válido devuelve los datos del usuario autenticado (ej. id, name, email).
    * Ambos endpoints devuelven 401 si no se provee token o es inválido.
* **Prioridad:** Alta

---

**Epic: Administración de la Tienda - Modelo de Datos y Roles**

* **ID del Ticket:** `TASK-CM-004`
* **Título:** Modelo de Datos - Añadir campo `is_admin` a tabla `users` y Migración
* **Historia(s) de Usuario Relacionada(s):** HU7.0 y todas las HU de Admin (Epic 7)
* **Descripción:**
    1.  Crear una nueva migración para añadir una columna `is_admin` (BOOLEAN, DEFAULT `false`, NOT NULL) a la tabla `users`.
    2.  Ejecutar la migración.
    3.  Actualizar el modelo `App\Models\User.php` para incluir `is_admin` en `$fillable` si se gestionará masivamente y en `$casts`.
    4.  Crear un middleware `EnsureUserIsAdmin` que verifique si el usuario autenticado tiene `is_admin = true`.
* **Criterios de Aceptación:**
    * La columna `is_admin` existe en la tabla `users` con el valor por defecto `false`.
    * El modelo User refleja el nuevo campo.
    * El middleware `EnsureUserIsAdmin` puede ser aplicado a rutas/controladores y deniega el acceso si el usuario no es admin.
    * Se puede asignar manualmente un usuario como administrador para pruebas.
* **Prioridad:** Alta

---

**Epic: Administración de la Tienda - Gestión de Categorías (Backend y API)**

* **ID del Ticket:** `TASK-CM-005`
* **Título:** Backend - Modelo, Migración, Factory y Seeders para Categorías
* **Historia(s) de Usuario Relacionada(s):** HU1.3, HU7.7, HU7.8, HU7.9, HU7.10
* **Descripción:**
    1.  Crear la migración para la tabla `categories` según el modelo de datos definido (id, name, slug, description, image_path, is_active, timestamps).
    2.  Crear el modelo Eloquent `App\Models\Category` con relaciones (ej. `products()`).
    3.  Crear un `CategoryFactory` para generar datos de prueba.
    4.  (Opcional) Crear un `CategorySeeder` para poblar con algunas categorías iniciales.
* **Criterios de Aceptación:**
    * La tabla `categories` se crea correctamente con todas sus columnas y restricciones.
    * El modelo `Category` está funcional.
    * Se pueden crear categorías usando la factory.
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-006`
* **Título:** Backend - API Endpoints (CRUD) para Gestión de Categorías (Protegido por Admin)
* **Historia(s) de Usuario Relacionada(s):** HU7.7, HU7.8, HU7.9, HU7.10
* **Descripción:**
    1.  Crear `Api/V1/Admin/CategoryController.php`.
    2.  Implementar métodos para:
        * `index()`: Listar todas las categorías (para admin, paginado).
        * `store()`: Crear nueva categoría (validar request, manejar subida de `image_path` si se provee).
        * `show()`: Mostrar una categoría específica.
        * `update()`: Actualizar una categoría (validar request, manejar subida/actualización de `image_path`).
        * `destroy()`: Eliminar una categoría (soft delete si se implementa, o borrado con validación de productos asociados).
    3.  Definir las rutas API RESTful para estos métodos bajo `/api/v1/admin/categories` y protegerlas con `auth:sanctum` y el middleware `EnsureUserIsAdmin`.
    4.  Utilizar API Resources para formatear las respuestas JSON.
* **Criterios de Aceptación:**
    * Un admin puede listar, crear, ver, actualizar y eliminar categorías a través de la API.
    * Las validaciones de datos funcionan correctamente.
    * La gestión de `image_path` (guardar ruta) funciona si se implementa subida.
    * Usuarios no administradores no pueden acceder a estos endpoints.
* **Prioridad:** Alta

---

**Epic: Administración de la Tienda - Gestión de Productos (Backend y API)**

* **ID del Ticket:** `TASK-CM-007`
* **Título:** Backend - Modelo, Migración, Factory y Seeders para Productos
* **Historia(s) de Usuario Relacionada(s):** HU1.1, HU1.2, HU1.5, HU7.1, HU7.2, HU7.3, HU7.4, HU7.5, HU7.6
* **Descripción:**
    1.  Crear la migración para la tabla `products` según el modelo de datos (id, category_id FK, name, slug, description, sku, price, stock_quantity, brand, movement_type, image_path, is_active, timestamps).
    2.  Crear el modelo Eloquent `App\Models\Product` con relaciones (ej. `category()`).
    3.  Crear un `ProductFactory` para generar datos de prueba.
    4.  (Opcional) Crear un `ProductSeeder` para poblar con algunos productos iniciales.
* **Criterios de Aceptación:**
    * La tabla `products` se crea correctamente con todas sus columnas y restricciones.
    * El modelo `Product` está funcional y sus relaciones definidas.
    * Se pueden crear productos usando la factory.
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-008`
* **Título:** Backend - Endpoint dedicado para Subida de Imágenes
* **Historia(s) de Usuario Relacionada(s):** HU7.2, HU7.3 (Admin), HU7.8, HU7.9 (Admin)
* **Descripción:**
    1.  Crear `Api/V1/Admin/ImageUploadController.php` (o similar).
    2.  Implementar un método `store()` que maneje la subida de un archivo de imagen (`multipart/form-data`).
    3.  Validar el archivo (tipo MIME, tamaño máximo).
    4.  Almacenar la imagen usando Laravel Filesystem (ej. en `storage/app/public/products` o `storage/app/public/categories` dependiendo de un parámetro `type` opcional en la request).
    5.  Devolver la ruta relativa de la imagen almacenada y la URL pública completa.
    6.  Definir la ruta `POST /api/v1/admin/images/upload` protegida por `auth:sanctum` y `EnsureUserIsAdmin`.
* **Criterios de Aceptación:**
    * Un admin puede subir un archivo de imagen válido.
    * La imagen se almacena en la ubicación correcta del servidor.
    * La API devuelve la ruta relativa y la URL pública de la imagen.
    * Se rechazan archivos inválidos (tipo/tamaño).
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-009`
* **Título:** Backend - API Endpoints (CRUD) para Gestión de Productos (Protegido por Admin)
* **Historia(s) de Usuario Relacionada(s):** HU7.1, HU7.2, HU7.3, HU7.4, HU7.5, HU7.6
* **Descripción:**
    1.  Crear `Api/V1/Admin/ProductController.php`.
    2.  Implementar métodos para:
        * `index()`: Listar todos los productos (para admin, paginado, con filtros básicos).
        * `store()`: Crear nuevo producto (validar request, asociar `image_path` obtenido del endpoint de subida).
        * `show()`: Mostrar un producto específico.
        * `update()`: Actualizar un producto (validar request, asociar/actualizar `image_path`).
        * `destroy()`: Eliminar un producto (soft delete).
        * Métodos adicionales para actualizar stock o estado `is_active` si no se hace en `update()`.
    3.  Definir las rutas API RESTful para estos métodos bajo `/api/v1/admin/products` y protegerlas con `auth:sanctum` y `EnsureUserIsAdmin`.
    4.  Utilizar API Resources para formatear las respuestas JSON.
* **Criterios de Aceptación:**
    * Un admin puede listar, crear, ver, actualizar y eliminar productos a través de la API.
    * Las validaciones de datos funcionan.
    * La asociación de `image_path` funciona.
    * La gestión de stock y estado `is_active` funciona.
    * Usuarios no administradores no pueden acceder.
* **Prioridad:** Alta

---

**Epic: Navegación y Descubrimiento de Productos (API Pública y Frontend)**

* **ID del Ticket:** `TASK-CM-010`
* **Título:** Backend - API Endpoints Públicos para Visualización de Categorías y Productos
* **Historia(s) de Usuario Relacionada(s):** HU1.1, HU1.2, HU1.3, HU1.4, HU1.5
* **Descripción:**
    1.  Crear `Api/V1/CategoryController.php` (para la parte pública). Implementar `index()` y `show()` que devuelvan categorías activas y, en `show()`, los productos asociados (paginados y activos).
    2.  Crear `Api/V1/ProductController.php` (para la parte pública). Implementar `index()` (con paginación, filtros por categoría, búsqueda básica, ordenamiento) y `show()` (por slug). Solo devolver productos activos.
    3.  Definir las rutas públicas `GET /api/v1/categories`, `GET /api/v1/categories/{slug}`, `GET /api/v1/products`, `GET /api/v1/products/{slug}`.
    4.  Usar API Resources para formatear las respuestas.
* **Criterios de Aceptación:**
    * `GET /api/v1/categories` devuelve lista de categorías activas.
    * `GET /api/v1/categories/{slug}` devuelve detalles de la categoría y sus productos activos paginados.
    * `GET /api/v1/products` devuelve lista de productos activos paginados, con filtros y ordenamiento funcionando.
    * `GET /api/v1/products/{slug}` devuelve detalles de un producto activo.
    * Los endpoints manejan correctamente casos de "no encontrado" (404).
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-011`
* **Título:** Frontend (React) - Página de Listado de Productos y Navegación por Categorías
* **Historia(s) de Usuario Relacionada(s):** HU1.1, HU1.3, HU1.4, HU1.5
* **Descripción:**
    1.  Desarrollar el componente React para la página de listado de productos (`ProductsIndexPage.tsx`).
    2.  Integrar con el endpoint `GET /api/v1/products` para mostrar los productos.
    3.  Implementar paginación en el frontend.
    4.  Implementar UI para filtros (lista de categorías obtenida de `GET /api/v1/categories`) y búsqueda básica, que actualicen las llamadas a la API.
    5.  Mostrar imagen, nombre, precio, marca, y disponibilidad de stock para cada producto.
    6.  Asegurar que los enlaces a detalle de producto y "Añadir al carrito" estén presentes.
* **Criterios de Aceptación:**
    * Se muestran los productos según los AC de HU1.1, HU1.3, HU1.4, HU1.5.
    * La paginación funciona.
    * El filtrado por categoría y la búsqueda actualizan la lista de productos.
* **Dependencias:** `TASK-CM-010`
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-012`
* **Título:** Frontend (React) - Página de Detalle de Producto
* **Historia(s) de Usuario Relacionada(s):** HU1.2, HU1.5
* **Descripción:**
    1.  Desarrollar el componente React para la página de detalle de producto (`ProductShowPage.tsx`).
    2.  Integrar con el endpoint `GET /api/v1/products/{slug}`.
    3.  Mostrar toda la información detallada del producto (nombre, descripción, precio, imagen, marca, tipo de movimiento, stock).
    4.  Incluir botón "Añadir al carrito" (funcionalidad se desarrolla en ticket de carrito).
* **Criterios de Aceptación:**
    * Se muestran todos los detalles del producto según AC de HU1.2 y HU1.5.
    * Si el producto no existe, se maneja el error elegantemente.
* **Dependencias:** `TASK-CM-010`
* **Prioridad:** Alta

---

**Epic: Gestión del Carrito de Compras (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-013`
* **Título:** Backend - Modelo, Migración y Lógica Base para Carrito (`carts`, `cart_items`)
* **Historia(s) de Usuario Relacionada(s):** HU2.1 a HU2.6
* **Descripción:**
    1.  Crear migraciones para las tablas `carts` y `cart_items` según el modelo de datos.
    2.  Crear modelos Eloquent `App\Models\Cart` y `App\Models\CartItem` con sus relaciones.
    3.  Implementar lógica en un `CartService` para:
        * Obtener/crear carrito para usuario autenticado o invitado (basado en `user_id` o `session_id`).
        * Añadir producto al carrito (verificar stock).
        * Actualizar cantidad de ítem.
        * Eliminar ítem.
        * Calcular totales del carrito.
        * Fusionar carrito de invitado al loguearse.
* **Criterios de Aceptación:**
    * Tablas `carts` y `cart_items` creadas.
    * Modelos funcionales.
    * `CartService` puede gestionar las operaciones básicas del carrito y la persistencia.
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-014`
* **Título:** Backend - API Endpoints para Gestión del Carrito
* **Historia(s) de Usuario Relacionada(s):** HU2.1 a HU2.4
* **Descripción:**
    1.  Crear `Api/V1/CartController.php`.
    2.  Implementar métodos usando `CartService` para:
        * `show()`: `GET /api/v1/cart` - Obtener carrito actual.
        * `addItem()`: `POST /api/v1/cart/items` - Añadir ítem.
        * `updateItem()`: `PUT /api/v1/cart/items/{cart_item_id}` - Actualizar cantidad.
        * `removeItem()`: `DELETE /api/v1/cart/items/{cart_item_id}` - Eliminar ítem.
    3.  Proteger los endpoints (Sanctum para usuarios, manejo de sesión para invitados).
    4.  Usar API Resources para formatear respuestas del carrito.
* **Criterios de Aceptación:**
    * Todos los endpoints del carrito funcionan según las especificaciones de API.
    * Se maneja correctamente la identificación de carritos de invitados y usuarios logueados.
    * Las validaciones de stock se aplican.
* **Dependencias:** `TASK-CM-013`
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-015`
* **Título:** Frontend (React) - UI y Lógica para Interacción con el Carrito de Compras
* **Historia(s) de Usuario Relacionada(s):** HU2.1 a HU2.6
* **Descripción:**
    1.  Desarrollar componentes React para:
        * Botón "Añadir al Carrito" en páginas de producto.
        * Indicador de carrito en la cabecera (conteo de ítems, enlace a la página del carrito).
        * Página del Carrito (`CartPage.tsx`) mostrando ítems, cantidades, precios, subtotal, opciones para actualizar cantidad y eliminar ítems.
    2.  Integrar estos componentes con los endpoints de la API del carrito (`TASK-CM-014`).
    3.  Manejar el estado global del carrito en React (ej. con Context API o Zustand/Redux si se prefiere).
* **Criterios de Aceptación:**
    * El cliente puede añadir productos, ver su carrito, modificar cantidades y eliminar ítems.
    * El carrito se actualiza visualmente y refleja los cambios hechos vía API.
    * Se cumplen los AC de HU2.1 a HU2.6 relacionados con la UI.
* **Dependencias:** `TASK-CM-014`
* **Prioridad:** Alta

---

**Epic: Gestión de Direcciones (Backend, API y Frontend - Usuario)**

* **ID del Ticket:** `TASK-CM-016`
* **Título:** Backend - Modelo, Migración y API (CRUD) para Libreta de Direcciones del Usuario
* **Historia(s) de Usuario Relacionada(s):** HU3.3, HU3.5, HU6.1 a HU6.6
* **Descripción:**
    1.  Crear migración para la tabla `addresses` según el modelo de datos.
    2.  Crear modelo Eloquent `App\Models\Address` con relación a `User`.
    3.  Crear `Api/V1/User/AddressController.php` con métodos CRUD (`index`, `store`, `update`, `destroy`) y para marcar como default (shipping/billing).
    4.  Definir rutas API bajo `/api/v1/user/addresses` protegidas por Sanctum.
    5.  Usar API Resources.
* **Criterios de Aceptación:**
    * Un usuario autenticado puede gestionar su libreta de direcciones vía API.
    * Las validaciones y la lógica de "dirección por defecto" funcionan.
* **Prioridad:** Media (Necesario para Checkout, pero no bloquea la visualización inicial de productos/carrito)

* **ID del Ticket:** `TASK-CM-017`
* **Título:** Frontend (React) - UI para Libreta de Direcciones en Perfil de Usuario
* **Historia(s) de Usuario Relacionada(s):** HU3.3, HU3.5, HU6.1 a HU6.6
* **Descripción:**
    1.  Desarrollar componentes React en la sección de perfil del usuario para:
        * Listar direcciones guardadas.
        * Formulario para añadir/editar dirección.
        * Opciones para eliminar y marcar como dirección por defecto.
    2.  Integrar con los endpoints de API de direcciones (`TASK-CM-016`).
* **Criterios de Aceptación:**
    * El usuario puede gestionar sus direcciones según las HU del Epic 6.
    * Las direcciones por defecto se marcan y se reflejan correctamente.
* **Dependencias:** `TASK-CM-003` (para perfil de usuario), `TASK-CM-016`
* **Prioridad:** Media

---

**Epic: Proceso de Compra (Checkout) y Pedidos (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-018`
* **Título:** Backend - Modelo, Migración y Lógica Base para Pedidos (`orders`, `order_items`)
* **Historia(s) de Usuario Relacionada(s):** HU3.1 a HU3.11, HU5.1, HU5.2
* **Descripción:**
    1.  Crear migraciones para `orders` y `order_items` según modelo de datos.
    2.  Crear modelos Eloquent `App\Models\Order` y `App\Models\OrderItem` con sus relaciones.
    3.  Implementar lógica en un `OrderService` para:
        * Crear un pedido a partir de un carrito (transferir ítems, calcular totales).
        * Validar datos del checkout (direcciones, etc.).
        * Actualizar stock de productos tras la creación del pedido.
        * Generar número de pedido único.
* **Criterios de Aceptación:**
    * Tablas `orders` y `order_items` creadas.
    * Modelos funcionales.
    * `OrderService` puede crear un pedido válido desde un carrito.
* **Dependencias:** `TASK-CM-013` (Lógica de Carrito)
* **Prioridad:** Muy Alta

* **ID del Ticket:** `TASK-CM-019`
* **Título:** Backend - Integración con Pasarela de Pago (PayPal Sandbox)
* **Historia(s) de Usuario Relacionada(s):** HU3.8
* **Descripción:**
    1.  Configurar SDK de PayPal o la integración API directa.
    2.  Implementar lógica en `PaymentService` o dentro de `OrderService` para:
        * Procesar un pago con los detalles/nonce proporcionados por el frontend.
        * Manejar respuestas de PayPal (éxito, error).
        * Actualizar el estado del pedido y del pago según la respuesta de PayPal.
    3.  (Opcional MVP, pero recomendado) Configurar webhooks de PayPal para confirmaciones de pago asíncronas.
* **Criterios de Aceptación:**
    * Se puede iniciar un proceso de pago con PayPal Sandbox.
    * Se puede simular un pago exitoso y uno fallido.
    * El estado del pedido se actualiza correctamente según el resultado del pago.
* **Prioridad:** Muy Alta

* **ID del Ticket:** `TASK-CM-020`
* **Título:** Backend - API Endpoint `POST /orders` para Crear Pedido (Checkout)
* **Historia(s) de Usuario Relacionada(s):** HU3.1 a HU3.8, HU3.11
* **Descripción:**
    1.  Crear `Api/V1/OrderController.php` con un método `store`.
    2.  El método debe:
        * Validar los datos de la petición (direcciones, detalles de pago, etc.).
        * Utilizar `CartService` para obtener el carrito actual.
        * Utilizar `OrderService` para crear el pedido en estado "pendiente_pago".
        * Utilizar `PaymentService` para procesar el pago.
        * Actualizar estado del pedido y stock si el pago es exitoso.
        * Devolver la respuesta apropiada (detalle del pedido creado o error).
    3.  Definir la ruta `POST /api/v1/orders` protegida (Sanctum o manejo de invitado).
* **Criterios de Aceptación:**
    * Se puede crear un pedido exitosamente a través de la API si todos los datos son válidos y el pago simulado es exitoso.
    * Las validaciones de datos funcionan.
    * El stock de los productos se reduce tras una compra exitosa.
    * Se manejan errores de la pasarela de pago.
* **Dependencias:** `TASK-CM-013`, `TASK-CM-018`, `TASK-CM-019`
* **Prioridad:** Muy Alta

* **ID del Ticket:** `TASK-CM-021`
* **Título:** Backend - Notificaciones por Email (Confirmación de Pedido)
* **Historia(s) de Usuario Relacionada(s):** HU3.10
* **Descripción:**
    1.  Crear una clase Mailable de Laravel para la confirmación de pedido (`OrderConfirmationMail.php`).
    2.  Diseñar una plantilla de email simple (Blade o Markdown) para la confirmación, incluyendo detalles del pedido.
    3.  Integrar el envío de este email en el `OrderService` después de que un pedido se haya completado y pagado exitosamente.
* **Criterios de Aceptación:**
    * Se envía un correo de confirmación al cliente tras una compra exitosa.
    * El correo contiene la información relevante del pedido.
* **Dependencias:** `TASK-CM-018`
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-022`
* **Título:** Frontend (React) - Flujo de Checkout Completo
* **Historia(s) de Usuario Relacionada(s):** HU3.1 a HU3.9, HU3.11
* **Descripción:**
    1.  Desarrollar los componentes React para las páginas/pasos del checkout (`CheckoutPage.tsx`):
        * Ingreso/selección de dirección de envío (integrar con `TASK-CM-017` si usuario logueado).
        * Ingreso/selección de dirección de facturación.
        * Selección de método de envío (para MVP, puede ser fijo o una opción simple).
        * Resumen del pedido.
        * Integración con el SDK de PayPal para obtener el nonce/detalles de pago.
    2.  Llamar al endpoint `POST /api/v1/orders` con todos los datos recopilados.
    3.  Manejar la respuesta y redirigir a la página de confirmación de pedido o mostrar errores.
* **Criterios de Aceptación:**
    * El cliente puede completar todos los pasos del checkout.
    * Los datos se envían correctamente a la API de creación de pedido.
    * La integración con PayPal (Sandbox) funciona en el frontend.
    * Se muestra la página de confirmación o errores.
* **Dependencias:** `TASK-CM-015` (para iniciar checkout), `TASK-CM-016` (opcional para direcciones), `TASK-CM-017` (opcional para direcciones), `TASK-CM-020` (API de pedidos)
* **Prioridad:** Muy Alta

* **ID del Ticket:** `TASK-CM-023`
* **Título:** Frontend (React) - Páginas "Mis Pedidos" y Detalle de Pedido para Usuario
* **Historia(s) de Usuario Relacionada(s):** HU5.1, HU5.2
* **Descripción:**
    1.  Crear API endpoints en backend: `GET /api/v1/user/orders` y `GET /api/v1/user/orders/{order_number}` (protegidos por Sanctum, el usuario solo ve sus pedidos).
    2.  Desarrollar componentes React para la sección "Mis Pedidos" del perfil del usuario:
        * Página de listado de pedidos (`UserOrdersPage.tsx`).
        * Página de detalle de pedido (`UserOrderDetailPage.tsx`).
    3.  Integrar con los nuevos endpoints API.
* **Criterios de Aceptación:**
    * Un usuario autenticado puede ver su historial de pedidos y el detalle de cada uno.
* **Dependencias:** `TASK-CM-003`, `TASK-CM-018`
* **Prioridad:** Media

---

**Epic: Administración de la Tienda - UI (Frontend React)**

* **ID del Ticket:** `TASK-CM-024`
* **Título:** Frontend (Admin) - Layout Básico y Autenticación para Panel de Administración
* **Historia(s) de Usuario Relacionada(s):** HU7.0
* **Descripción:**
    1.  Crear un layout base para el panel de administración en React (`AdminLayout.tsx`).
    2.  Implementar lógica de enrutamiento en React para las secciones de admin (ej. `/admin/products`, `/admin/categories`, `/admin/orders`).
    3.  Asegurar que estas rutas estén protegidas y solo sean accesibles si el usuario está autenticado vía API (token Sanctum) y tiene `is_admin = true`.
    4.  El login de admin puede usar el mismo endpoint `POST /api/v1/auth/login`, pero el frontend redirigirá a `/admin/dashboard` si `user.is_admin` es true.
* **Criterios de Aceptación:**
    * Existe un layout para el panel de admin.
    * Solo usuarios administradores pueden acceder a las rutas del panel de admin.
* **Dependencias:** `TASK-CM-002`, `TASK-CM-004`
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-025`
* **Título:** Frontend (Admin) - UI para Gestión de Productos (CRUD)
* **Historia(s) de Usuario Relacionada(s):** HU7.1, HU7.2, HU7.3, HU7.4, HU7.5, HU7.6
* **Descripción:**
    1.  Desarrollar componentes React para:
        * Listar productos (`AdminProductsListPage.tsx`) con paginación, búsqueda/filtros básicos y acciones (editar, eliminar, cambiar estado).
        * Formulario de creación/edición de producto (`AdminProductFormPage.tsx`) con todos los campos, incluida la subida de imagen (integrar con `TASK-CM-008`).
    2.  Integrar con los endpoints API de admin para productos (`TASK-CM-009`).
* **Criterios de Aceptación:**
    * El administrador puede listar, crear, editar (incluida imagen y stock), activar/desactivar y eliminar (soft delete) productos a través de la UI.
    * Las validaciones de formulario se muestran al usuario.
* **Dependencias:** `TASK-CM-009`, `TASK-CM-008`, `TASK-CM-024`
* **Prioridad:** Alta

* **ID del Ticket:** `TASK-CM-026`
* **Título:** Frontend (Admin) - UI para Gestión de Categorías (CRUD Básico)
* **Historia(s) de Usuario Relacionada(s):** HU7.7, HU7.8, HU7.9, HU7.10
* **Descripción:**
    1.  Desarrollar componentes React para:
        * Listar categorías (`AdminCategoriesListPage.tsx`) con acciones (editar, eliminar).
        * Formulario de creación/edición de categoría (`AdminCategoryFormPage.tsx`) con nombre, descripción e imagen opcional (integrar con `TASK-CM-008`).
    2.  Integrar con los endpoints API de admin para categorías (`TASK-CM-006`).
* **Criterios de Aceptación:**
    * El administrador puede listar, crear, editar y eliminar categorías.
    * Se maneja la subida opcional de imagen para categorías.
* **Dependencias:** `TASK-CM-006`, `TASK-CM-008`, `TASK-CM-024`
* **Prioridad:** Media

* **ID del Ticket:** `TASK-CM-027`
* **Título:** Frontend (Admin) - UI para Visualización de Pedidos y Actualización de Estado
* **Historia(s) de Usuario Relacionada(s):** HU7.11, HU7.12, HU7.13
* **Descripción:**
    1.  Crear API Endpoints para Administración de Pedidos (`GET /api/v1/admin/orders`, `GET /api/v1/admin/orders/{id}`, `PUT /api/v1/admin/orders/{id}/status`) protegidos por admin.
    2.  Desarrollar componentes React para:
        * Listar todos los pedidos (`AdminOrdersListPage.tsx`) con paginación y filtros básicos.
        * Ver detalle de un pedido (`AdminOrderDetailPage.tsx`).
        * Modificar el estado de un pedido.
    3.  Integrar con los nuevos endpoints API de admin para pedidos.
* **Criterios de Aceptación:**
    * El administrador puede listar todos los pedidos y ver sus detalles.
    * El administrador puede cambiar el estado de un pedido.
* **Dependencias:** `TASK-CM-018` (modelos de pedido), `TASK-CM-024`
* **Prioridad:** Media

