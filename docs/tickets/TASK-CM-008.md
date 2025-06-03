**Epic: Administración de la Tienda - Gestión de Productos (Backend y API)**

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
