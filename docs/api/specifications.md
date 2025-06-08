# 4. Especificaciones de la API - CronosMatic

## a. Introducción General a la API

* **URL Base:** `/api/v1` (Ejemplo de desarrollo: `http://cronosmatic.test/api/v1/`, producción: `https://tu-dominio.com/api/v1/`)
* **Formato de Datos:** Todas las peticiones (`Content-Type: application/json`) y respuestas (`Accept: application/json`) utilizarán el formato `JSON`.
* **Autenticación:**
    * **Rutas Públicas:** No requieren autenticación (ej. listado/detalle de productos y categorías).
    * **Rutas Protegidas:** Requerirán un token API de **Laravel Sanctum**. El token deberá enviarse en la cabecera `Authorization` como `Bearer {token}`. Este token se obtendrá a través de los endpoints de API `/auth/login` o `/auth/register`.
* **Convenciones:**
    * **Nombres de Claves JSON:** Se utilizará `snake_case` (ej. `order_number`).
    * **URLs de Recursos:** Se utilizará inglés y en plural (ej. `/products`, `/orders`, `/categories`).
* **Manejo de Errores Comunes:** Se utilizarán códigos de estado HTTP estándar. Las respuestas de error incluirán un cuerpo JSON con el siguiente formato:
    * General: `{"message": "Descripción del error."}`
    * Errores de Validación (422):
        ```json
        {
          "message": "The given data was invalid.",
          "errors": {
            "nombre_del_campo": ["Mensaje de error específico para este campo."]
          }
        }
        ```
    * **Códigos Comunes:**
        * `200 OK`: Petición exitosa.
        * `201 Created`: Recurso creado exitosamente.
        * `204 No Content`: Petición exitosa sin cuerpo de respuesta (ej. para `DELETE` o `logout`).
        * `400 Bad Request`: Petición mal formada (ej. JSON inválido).
        * `401 Unauthorized`: Autenticación requerida o fallida.
        * `403 Forbidden`: Autenticado pero sin permisos para la acción.
        * `404 Not Found`: Recurso no encontrado.
        * `422 Unprocessable Entity`: Errores de validación de datos.
        * `500 Internal Server Error`: Error inesperado en el servidor.

## b. Definición de Endpoints por Recurso/Funcionalidad (MVP)

A continuación, se detallan los endpoints para cada recurso principal.

### i. Autenticación (`/auth`)

* **`POST /auth/register`**
    * **Auth:** Pública
    * **Descripción:** Registra un nuevo usuario.
    * **Cuerpo Petición (JSON):** `{"name": "string", "email": "string", "password": "string", "password_confirmation": "string"}`
    * **Respuesta Exitosa (201 Created):** `{"data": {"user": {user_object}, "token": "string_api_token"}}`
    * **Errores:** 422 (Validación)

* **`POST /auth/login`**
    * **Auth:** Pública
    * **Descripción:** Inicia sesión para un usuario existente.
    * **Cuerpo Petición (JSON):** `{"email": "string", "password": "string", "remember_me": "boolean_opcional"}`
    * **Respuesta Exitosa (200 OK):** `{"data": {"user": {user_object}, "token": "string_api_token"}}`
    * **Errores:** 422 (Validación), 401 (Credenciales incorrectas)

* **`POST /auth/logout`**
    * **Auth:** Sanctum (Token Bearer)
    * **Descripción:** Cierra la sesión del usuario invalidando el token actual.
    * **Respuesta Exitosa (204 No Content):** (Sin cuerpo)
    * **Errores:** 401 (No autenticado)

* **`GET /auth/user`**
    * **Auth:** Sanctum (Token Bearer)
    * **Descripción:** Obtiene la información del usuario autenticado actualmente.
    * **Respuesta Exitosa (200 OK):** `{"data": {user_object_completo}}`
    * **Errores:** 401 (No autenticado)

### ii. Productos (`/products`)

* **`GET /products`**
    * **Auth:** Pública
    * **Descripción:** Lista los productos disponibles, con paginación y filtros básicos.
    * **Parámetros Query:**
        * `page` (int, opcional): Número de página.
        * `per_page` (int, opcional): Productos por página.
        * `category_slug` (string, opcional): Slug de la categoría para filtrar.
        * `sort_by` (string, opcional): Criterio de orden (`price_asc`, `price_desc`, `name_asc`, `name_desc`, `created_at_desc`).
        * `search` (string, opcional): Término de búsqueda (en nombre, SKU, descripción).
    * **Respuesta Exitosa (200 OK):** Formato de paginación de Laravel: `{"data": [{product_summary_object}], "links": {...}, "meta": {...}}`
        * `product_summary_object`: `{ "id", "name", "slug", "price", "image_url" (URL completa generada por backend), "brand", "movement_type" }`

* **`GET /products/{slug}`**
    * **Auth:** Pública
    * **Descripción:** Obtiene los detalles de un producto específico por su slug.
    * **Parámetros URL:** `slug` (string)
    * **Respuesta Exitosa (200 OK):** `{"data": {product_detail_object}}`
        * `product_detail_object`: `{ "id", "name", "slug", "description", "sku", "price", "stock_quantity", "brand", "movement_type", "image_url" (URL completa generada por backend), "category": {category_summary_object_opcional} }`
    * **Errores:** 404 (Producto no encontrado)

### iii. Categorías (`/categories`)

* **`GET /categories`**
    * **Auth:** Pública
    * **Descripción:** Lista todas las categorías activas.
    * **Respuesta Exitosa (200 OK):** `{"data": [{category_object}]}`
        * `category_object`: `{ "id", "name", "slug", "description", "image_url" (URL completa generada por backend) }`

* **`GET /categories/{slug}`**
    * **Auth:** Pública
    * **Descripción:** Obtiene detalles de una categoría y una lista paginada de sus productos.
    * **Parámetros URL:** `slug` (string)
    * **Parámetros Query (para productos):** `page` (int, opcional), `per_page` (int, opcional), `sort_by` (string, opcional)
    * **Respuesta Exitosa (200 OK):** `{"data": {"category": {category_object}, "products": {paginated_product_summary_object}}}`
    * **Errores:** 404 (Categoría no encontrada)

### iv. Carrito (`/cart`)

* **`GET /cart`**
    * **Auth:** Sanctum (o sesión de invitado gestionada por backend)
    * **Descripción:** Obtiene el contenido del carrito actual del usuario/invitado.
    * **Respuesta Exitosa (200 OK):** `{"data": {"items": [{cart_item_object}], "subtotal": "decimal", "total_items": "int"}}`
        * `cart_item_object`: `{ "id" (cart_item_id), "product_id", "product_name", "quantity", "price_per_unit", "total_price", "image_url" (URL completa generada por backend) }`

* **`POST /cart/items`**
    * **Auth:** Sanctum (o sesión de invitado)
    * **Descripción:** Añade un producto al carrito o actualiza su cantidad si ya existe.
    * **Cuerpo Petición (JSON):** `{"product_id": "integer", "quantity": "integer"}`
    * **Respuesta Exitosa (200 OK o 201 Created):** `{"data": {cart_object_actualizado}}`
    * **Errores:** 422 (Validación, ej. producto no existe, stock insuficiente), 404 (Producto no encontrado)

* **`PUT /cart/items/{cart_item_id}`**
    * **Auth:** Sanctum (o sesión de invitado)
    * **Descripción:** Actualiza la cantidad de un ítem específico en el carrito.
    * **Parámetros URL:** `cart_item_id` (integer)
    * **Cuerpo Petición (JSON):** `{"quantity": "integer"}` (si quantity es 0, se elimina el ítem)
    * **Respuesta Exitosa (200 OK):** `{"data": {cart_object_actualizado}}`
    * **Errores:** 404 (Ítem no encontrado en carrito), 422 (Validación, ej. stock insuficiente)

* **`DELETE /cart/items/{cart_item_id}`**
    * **Auth:** Sanctum (o sesión de invitado)
    * **Descripción:** Elimina un ítem específico del carrito.
    * **Parámetros URL:** `cart_item_id` (integer)
    * **Respuesta Exitosa (200 OK):** `{"data": {cart_object_actualizado}}` o `204 No Content`.
    * **Errores:** 404 (Ítem no encontrado en carrito)

### v. Direcciones (`/user/addresses`) (Para libreta de direcciones del usuario)

* **`GET /user/addresses`**
    * **Auth:** Sanctum
    * **Descripción:** Lista las direcciones guardadas por el usuario autenticado.
    * **Parámetros Query:** `type` (string, opcional: 'shipping' o 'billing') - Filtra por tipo de dirección
    * **Respuesta Exitosa (200 OK):** `{"data": [{address_object_completo}]}`

* **`POST /user/addresses`**
    * **Auth:** Sanctum
    * **Descripción:** Añade una nueva dirección a la libreta del usuario.
    * **Cuerpo Petición (JSON):**
        ```json
        {
          "type": "shipping|billing",
          "first_name": "string",
          "last_name": "string", 
          "company": "string_opcional",
          "address_line_1": "string",
          "address_line_2": "string_opcional",
          "city": "string",
          "state": "string",
          "postal_code": "string",
          "country": "string",
          "phone": "string_opcional",
          "is_default": "boolean_opcional"
        }
        ```
    * **Respuesta Exitosa (201 Created):** `{"data": {address_object_creado}}`
    * **Errores:** 422 (Validación)

* **`PUT /user/addresses/{address_id}`**
    * **Auth:** Sanctum
    * **Descripción:** Actualiza una dirección existente del usuario.
    * **Parámetros URL:** `address_id` (integer)
    * **Cuerpo Petición (JSON):** (Mismos campos que el `POST`)
    * **Respuesta Exitosa (200 OK):** `{"data": {address_object_actualizado}}`
    * **Errores:** 403 (No autorizado para esta dirección), 404 (Dirección no encontrada), 422 (Validación)

* **`DELETE /user/addresses/{address_id}`**
    * **Auth:** Sanctum
    * **Descripción:** Elimina una dirección de la libreta del usuario.
    * **Parámetros URL:** `address_id` (integer)
    * **Respuesta Exitosa (204 No Content):** (Sin cuerpo)
    * **Errores:** 403, 404

* **`PATCH /user/addresses/{address_id}/set-default`**
    * **Auth:** Sanctum
    * **Descripción:** Marca una dirección como predeterminada para su tipo (shipping o billing).
    * **Parámetros URL:** `address_id` (integer)
    * **Respuesta Exitosa (200 OK):** `{"data": {address_object_actualizado}}`
    * **Errores:** 403, 404

**Objeto Address completo:**
```json
{
  "id": 1,
  "type": "shipping",
  "first_name": "Juan",
  "last_name": "Pérez",
  "full_name": "Juan Pérez",
  "company": "Mi Empresa S.A.",
  "address_line_1": "Av. Reforma 123",
  "address_line_2": "Col. Centro, Piso 5",
  "city": "Ciudad de México",
  "state": "CDMX",
  "postal_code": "06000",
  "country": "México",
  "phone": "+52 55 1234 5678",
  "is_default": true,
  "full_address": "Av. Reforma 123, Col. Centro, Piso 5, Ciudad de México, CDMX 06000, México",
  "created_at": "2024-01-15T10:30:00.000000Z",
  "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

**Notas sobre el modelo de direcciones:**
- El campo `type` define si es dirección de 'shipping' o 'billing'
- Solo puede haber una dirección por defecto por tipo por usuario
- El campo `full_name` es computado automáticamente a partir de `first_name` y `last_name`
- El campo `full_address` es computado automáticamente con el formato completo de la dirección
- Al crear/actualizar una dirección como `is_default: true`, automáticamente se marca como `false` cualquier otra dirección del mismo tipo

### vi. Pedidos (`/orders`)

* **`POST /orders`** (Checkout)
    * **Auth:** Sanctum (o sesión de invitado con `guest_email` proporcionado)
    * **Descripción:** Crea un nuevo pedido a partir del carrito actual y procesa el pago.
    * **Cuerpo Petición (JSON):**
        ```json
        {
          "shipping_address_id": "integer_opcional",
          "billing_address_id": "integer_opcional",
          "new_shipping_address": { /* address_object_opcional */ },
          "new_billing_address": { /* address_object_opcional */ },
          "guest_email": "string_opcional",
          "shipping_method_name": "string",
          "shipping_cost": "decimal",
          "payment_details": {
            "type": "paypal",
            "nonce": "string_nonce_de_paypal_sdk"
          },
          "notes": "string_opcional"
        }
        ```
        *(Nota: Se debe enviar `shipping_address_id` O `new_shipping_address`. Similar para facturación).*
    * **Respuesta Exitosa (201 Created):** `{"data": {order_object_creado_con_estado_pago}}`
    * **Errores:** 422 (Validación), 400 (Error pasarela)

* **`GET /orders`**
    * **Auth:** Sanctum
    * **Descripción:** Lista los pedidos del usuario autenticado (paginado).
    * **Parámetros Query:** `page` (int, opcional), `per_page` (int, opcional)
    * **Respuesta Exitosa (200 OK):** `{"data": [{order_summary_object}], "links": {...}, "meta": {...}}`
        * `order_summary_object`: `{ "id", "order_number", "created_at", "total_amount", "status", "payment_status" }`

* **`GET /orders/{order_number}`**
    * **Auth:** Sanctum (el usuario solo puede ver sus propios pedidos)
    * **Descripción:** Obtiene los detalles de un pedido específico.
    * **Parámetros URL:** `order_number` (string)
    * **Respuesta Exitosa (200 OK):** `{"data": {order_detail_object_con_items_y_direcciones}}`
    * **Errores:** 403 (No autorizado), 404 (Pedido no encontrado)

### vii. Carga de Imágenes (`/images`)

* **`POST /images/upload`**
    * **Auth:** Sanctum (requiere permisos de administrador para imágenes de productos/categorías)
    * **Descripción:** Sube una imagen al servidor. El backend la almacenará y devolverá la ruta.
    * **Cuerpo Petición:** `multipart/form-data` con un campo `image` (archivo). Opcional: `type` (string: "product", "category") para organizar el almacenamiento.
    * **Respuesta Exitosa (201 Created):** `{"data": {"image_path": "string_ruta_relativa_almacenada", "image_url": "string_url_completa_accesible_via_symlink"}}`
    * **Errores:** 422 (Validación de archivo: tipo no permitido, tamaño excedido), 403 (No autorizado)

## c. Detalle de Peticiones/Respuestas Clave (Ejemplos)

### Ejemplo 1: Listar Productos (`GET /api/v1/products?category_slug=relojes-deportivos&page=1`)

* **Método:** `GET`
* **URL:** `/api/v1/products?category_slug=relojes-deportivos&page=1`
* **Cabeceras:** `Accept: application/json`
* **Respuesta Exitosa (200 OK):**
    ```json
    {
      "data": [
        {
          "id": 1,
          "name": "Reloj Deportivo Alfa",
          "slug": "reloj-deportivo-alfa",
          "price": "4999.99",
          "image_url": "[http://cronosmatic.test/storage/products/reloj-alfa.jpg](http://cronosmatic.test/storage/products/reloj-alfa.jpg)",
          "brand": "CronosMatic Originals",
          "movement_type": "Automático"
        }
      ],
      "links": {
        "first": "[http://cronosmatic.test/api/v1/products?category_slug=relojes-deportivos&page=1](http://cronosmatic.test/api/v1/products?category_slug=relojes-deportivos&page=1)",
        "last": "[http://cronosmatic.test/api/v1/products?category_slug=relojes-deportivos&page=5](http://cronosmatic.test/api/v1/products?category_slug=relojes-deportivos&page=5)",
        "prev": null,
        "next": "[http://cronosmatic.test/api/v1/products?category_slug=relojes-deportivos&page=2](http://cronosmatic.test/api/v1/products?category_slug=relojes-deportivos&page=2)"
      },
      "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "path": "[http://cronosmatic.test/api/v1/products](http://cronosmatic.test/api/v1/products)",
        "per_page": 15,
        "to": 15,
        "total": 75
      }
    }
    ```

### Ejemplo 2: Añadir Producto al Carrito (`POST /api/v1/cart/items`)

* **Método:** `POST`
* **URL:** `/api/v1/cart/items`
* **Auth:** Sanctum (o sesión de invitado)
* **Cabeceras:** `Accept: application/json`, `Content-Type: application/json` (+ `Authorization: Bearer {token}` si usuario logueado)
* **Cuerpo Petición:**
    ```json
    {
      "product_id": 123,
      "quantity": 1
    }
    ```
* **Respuesta Exitosa (200 OK):** (Devuelve el estado completo del carrito actualizado)
    ```json
    {
      "data": {
        "items": [
          {
            "id": 1,
            "product_id": 123,
            "product_name": "Reloj Cronos X1",
            "quantity": 1,
            "price_per_unit": "2500.00",
            "total_price": "2500.00",
            "image_url": "[http://cronosmatic.test/storage/products/reloj-cronos-x1.jpg](http://cronosmatic.test/storage/products/reloj-cronos-x1.jpg)"
          }
        ],
        "subtotal": "2500.00",
        "total_items": 1
      }
    }
    ```
* **Respuesta Error (422 Unprocessable Entity - Stock insuficiente):**
    ```json
    {
      "message": "No hay suficiente stock para el producto Reloj Cronos X1.",
      "errors": {
        "quantity": ["La cantidad solicitada excede el stock disponible."]
      }
    }
    ```

## d. Consideraciones Adicionales (MVP)

* **Paginación:** Las respuestas de listado usarán el formato de paginación estándar de Laravel. El cliente puede enviar `?page=X&per_page=Y`.
* **Filtrado y Ordenamiento:** Se implementarán filtros básicos por query parameters (ej. `category_slug`, `search` para productos) y ordenamiento (ej. `sort_by=price_asc`).
* **Versionado:** La API está versionada con `/v1/` en la URL base.
* **Manejo de Carrito de Invitado:** El backend asociará carritos de invitados a `session_id`. Al hacer login/registro, se intentará fusionar el carrito de invitado con el del usuario.