**5. Historias de Usuario - CronosMatic (con Criterios de Aceptación)**

**Epic 1: Navegación y Descubrimiento de Productos**

* **Rol:** Cliente (Visitante o Registrado)
    * **HU1.1:** Como cliente, quiero ver una lista de relojes disponibles (con imagen, nombre, precio y marca) para poder explorar el catálogo.
        * **AC1:** Al visitar la página principal de la tienda o la sección de catálogo, se muestra una cuadrícula o lista de productos.
        * **AC2:** Cada producto en la lista debe mostrar su imagen principal, nombre, precio (en MXN) y marca de forma clara.
        * **AC3:** Si la cantidad de productos excede un límite predefinido por vista (ej. 12 productos), la lista debe estar paginada.
        * **AC4:** En la lista solo se deben mostrar productos que estén marcados como "activos" por el administrador.
    * **HU1.2:** Como cliente, quiero poder hacer clic en un reloj de la lista para ver sus detalles completos (nombre, descripción, precio, imagen, marca, tipo de movimiento, disponibilidad de stock) y así tomar una decisión de compra.
        * **AC1:** Al hacer clic en la imagen o nombre de un producto en la lista, soy redirigido a la página de detalle de ese producto.
        * **AC2:** La página de detalle del producto muestra: nombre completo, descripción detallada, precio (en MXN), imagen principal (de mayor tamaño), marca, tipo de movimiento y la disponibilidad actual de stock (ej. "En Stock" o "Agotado").
        * **AC3:** Si intento acceder a un producto que no existe o no está activo, se me muestra una página de "Producto no encontrado" o soy redirigido a una página de categoría/inicio.
    * **HU1.3:** Como cliente, quiero poder filtrar o navegar por categorías de relojes (ej. "Deportivos", "De Lujo", "Automáticos") para encontrar más fácilmente el tipo de reloj que busco.
        * **AC1:** Existe un menú de navegación (ej. barra lateral, menú superior) donde se listan las categorías principales de relojes.
        * **AC2:** Al hacer clic en el nombre de una categoría, la lista de productos se actualiza mostrando únicamente los relojes pertenecientes a esa categoría seleccionada.
        * **AC3:** El nombre de la categoría activa o el filtro aplicado se indica claramente en la página.
    * **HU1.4:** Como cliente, quiero poder realizar una búsqueda básica por palabras clave (que busque en nombre, marca, descripción) para encontrar relojes específicos rápidamente.
        * **AC1:** Hay un campo de entrada de texto para búsqueda, claramente visible en la interfaz (ej. en la cabecera).
        * **AC2:** Al ingresar un término de búsqueda y presionar "Enter" o un botón de "Buscar", la lista de productos se actualiza para mostrar solo aquellos productos cuyo nombre, marca o descripción contengan el término buscado.
        * **AC3:** Si la búsqueda no arroja resultados, se muestra un mensaje claro como "No se encontraron productos para '[término buscado]'".
    * **HU1.5:** Como cliente, quiero ver claramente si un producto está en stock antes de intentar añadirlo al carrito.
        * **AC1:** Tanto en la lista de productos como en la página de detalle del producto, se muestra una indicación visual clara del estado del stock (ej. "Disponible", "Pocas unidades", "Agotado").
        * **AC2:** Si un producto está "Agotado", el botón "Añadir al Carrito" debe estar deshabilitado o no visible.

**Epic 2: Gestión del Carrito de Compras**

* **Rol:** Cliente (Visitante o Registrado)
    * **HU2.1:** Como cliente, quiero poder añadir un reloj a mi carrito de compras desde la página de listado o la de detalle del producto.
        * **AC1:** En cada producto (listado/detalle) que tenga stock disponible, existe un botón visible de "Añadir al Carrito".
        * **AC2:** Al hacer clic en "Añadir al Carrito", el producto se agrega al carrito y se muestra una notificación o feedback visual (ej. "Producto añadido al carrito", el ícono del carrito actualiza su contador).
        * **AC3:** Si el producto ya estaba en el carrito, se incrementa su cantidad (hasta el límite de stock si aplica).
        * **AC4:** No es posible añadir al carrito un producto que no tiene stock disponible.
    * **HU2.2:** Como cliente, quiero poder ver fácilmente el contenido de mi carrito de compras (resumen de productos, cantidad de cada uno, precio unitario, precio total por ítem y subtotal del carrito) en cualquier momento.
        * **AC1:** Un ícono o enlace al carrito, que muestra el número de ítems, es visible persistentemente en la cabecera de la página.
        * **AC2:** Al hacer clic en el ícono/enlace del carrito, soy dirigido a la página del carrito de compras.
        * **AC3:** La página del carrito muestra una lista de cada producto: imagen miniatura, nombre, cantidad seleccionada, precio unitario (MXN), y precio total para ese ítem (cantidad * precio unitario).
        * **AC4:** Al final de la lista de ítems, se muestra el subtotal acumulado de todos los productos en el carrito.
    * **HU2.3:** Como cliente, quiero poder modificar la cantidad de un reloj específico en mi carrito de compras.
        * **AC1:** En la página del carrito, cada ítem listado tiene un control (ej. botones +/- o un campo numérico) para ajustar la cantidad.
        * **AC2:** Al modificar la cantidad, el precio total del ítem y el subtotal del carrito se recalculan y actualizan visiblemente de forma inmediata.
        * **AC3:** No puedo ingresar una cantidad que exceda el stock disponible para ese producto.
        * **AC4:** Si la cantidad se establece en 0, el ítem se elimina automáticamente del carrito o se presenta una opción para eliminarlo.
    * **HU2.4:** Como cliente, quiero poder eliminar un reloj de mi carrito de compras.
        * **AC1:** Cada ítem en la página del carrito tiene un botón o ícono claramente identificable para "Eliminar".
        * **AC2:** Al hacer clic en "Eliminar", el ítem desaparece de la lista del carrito.
        * **AC3:** El subtotal del carrito se recalcula y actualiza visiblemente.
    * **HU2.5:** Como cliente, quiero que los productos añadidos a mi carrito se mantengan allí si navego a otras páginas de la tienda.
        * **AC1:** Si añado productos al carrito y luego navego a otras secciones de la tienda (ej. otra categoría, página de inicio), al regresar al carrito o ver el indicador del carrito, los ítems y cantidades persisten.
    * **HU2.6:** Como cliente registrado, quiero que mi carrito de compras persista incluso si cierro sesión y vuelvo a iniciarla más tarde. (Para invitados, la persistencia será a través de la sesión del navegador).
        * **AC1:** (Usuario Registrado) Si añado ítems al carrito, cierro sesión y luego vuelvo a iniciar sesión con la misma cuenta, los ítems previamente añadidos están presentes en mi carrito.
        * **AC2:** (Invitado a Registrado) Si tengo ítems en mi carrito como invitado y durante el proceso de compra decido registrarme o iniciar sesión, los ítems de mi carrito de invitado se transfieren/fusionan con el carrito de mi cuenta de usuario.

**Epic 3: Proceso de Compra (Checkout)**

* **Rol:** Cliente (Visitante o Registrado)
    * **HU3.1:** Como cliente, quiero poder proceder al checkout desde mi carrito de compras para finalizar mi compra.
        * **AC1:** En la página del carrito, si este no está vacío, hay un botón claramente visible como "Finalizar Compra" o "Proceder al Pago".
        * **AC2:** Al hacer clic en dicho botón, soy redirigido al primer paso del proceso de checkout.
    * **HU3.2:** Como cliente, quiero poder ingresar mi dirección de envío completa.
        * **AC1:** En el proceso de checkout, se presenta un formulario que solicita: nombre completo del destinatario, calle y número, número interior/apartamento (opcional), código postal, ciudad, estado, y número de teléfono de contacto. El país por defecto es México.
        * **AC2:** Se realizan validaciones en los campos obligatorios (nombre, calle, CP, ciudad, estado, teléfono).
    * **HU3.3:** Como cliente registrado, quiero poder seleccionar una dirección de envío de mi libreta de direcciones guardada.
        * **AC1:** Si estoy autenticado y tengo direcciones guardadas, se me presenta una opción para seleccionar una de ellas en lugar de ingresar una nueva.
        * **AC2:** Al seleccionar una dirección de mi libreta, los campos del formulario de envío se rellenan automáticamente con los datos de la dirección seleccionada.
        * **AC3:** Tengo la opción de ingresar una nueva dirección de envío incluso si tengo direcciones guardadas.
    * **HU3.4:** Como cliente, quiero poder ingresar mi dirección de facturación, o indicar que es la misma que la dirección de envío.
        * **AC1:** En el proceso de checkout, se ofrece la opción de "Usar la misma dirección de envío para facturación".
        * **AC2:** Si no se marca la opción anterior, se presenta un formulario para ingresar una dirección de facturación diferente, con los mismos campos que la de envío.
    * **HU3.5:** Como cliente registrado, quiero poder seleccionar una dirección de facturación de mi libreta de direcciones guardada.
        * **AC1:** Si estoy autenticado, puedo seleccionar una dirección de mi libreta para la facturación (si no uso la misma que la de envío).
        * **AC2:** Al seleccionar, los campos del formulario de facturación se rellenan automáticamente.
    * **HU3.6:** Como cliente, quiero poder ver el costo de envío calculado (para MVP, puede ser un costo fijo único o gratuito) antes de pagar.
        * **AC1:** Durante el checkout, después de ingresar la dirección de envío, se muestra claramente el método de envío (ej. "Envío Estándar MXN") y su costo.
        * **AC2:** El costo de envío se refleja en el resumen del pedido.
    * **HU3.7:** Como cliente, quiero ver un resumen claro y final de mi pedido (productos, cantidades, precios, subtotal, costo de envío, total a pagar) antes de proceder al pago.
        * **AC1:** Antes de la sección de pago, se muestra un resumen detallado del pedido que incluye: lista de productos con cantidad y precio, subtotal, costo de envío y el monto total final en MXN.
    * **HU3.8:** Como cliente, quiero poder realizar el pago de mi pedido de forma segura utilizando una pasarela de pago (ej. PayPal Sandbox).
        * **AC1:** Se presenta la opción de pago con PayPal (ej. botón de PayPal).
        * **AC2:** Al seleccionar PayPal, soy redirigido al entorno seguro de PayPal (Sandbox) para completar el pago o se abre un modal de PayPal.
        * **AC3:** Después de autorizar/cancelar el pago en PayPal, soy redirigido de vuelta a la tienda CronosMatic.
    * **HU3.9:** Como cliente, quiero recibir una confirmación visual en pantalla de que mi pedido ha sido realizado exitosamente tras el pago.
        * **AC1:** Si el pago es exitoso y el pedido se crea, se muestra una página de "Pedido Confirmado" o "Gracias por tu compra".
        * **AC2:** Esta página muestra mi número de pedido y un mensaje indicando que se ha enviado un correo de confirmación.
    * **HU3.10:** Como cliente, quiero recibir un correo electrónico de confirmación con los detalles de mi pedido después de una compra exitosa.
        * **AC1:** Se envía un correo electrónico automático a la dirección que proporcioné (o la de mi cuenta).
        * **AC2:** El correo contiene: número de pedido, fecha, resumen de productos comprados (nombre, cantidad, precio), dirección de envío, y total pagado.
    * **HU3.11:** Como cliente invitado (no registrado), quiero poder completar todo el proceso de compra proporcionando mi correo electrónico para las notificaciones del pedido.
        * **AC1:** El flujo de checkout no me obliga a iniciar sesión o registrarme.
        * **AC2:** Durante el checkout, se me solicita obligatoriamente una dirección de correo electrónico para enviar las confirmaciones y actualizaciones del pedido.
        * **AC3:** Puedo ingresar mis direcciones de envío y facturación como invitado.

**Epic 4: Gestión de Cuenta de Usuario**

* **Rol:** Visitante / Cliente Registrado
    * **HU4.1:** Como visitante, quiero poder registrarme en la tienda proporcionando mi nombre, correo electrónico y una contraseña, para así tener una cuenta personal.
        * **AC1:** Hay un enlace/botón "Registrarse" visible y accesible.
        * **AC2:** El formulario de registro solicita como mínimo: nombre, correo electrónico, contraseña y confirmación de contraseña.
        * **AC3:** El sistema valida que el correo no esté ya registrado y que las contraseñas coincidan y cumplan un mínimo de seguridad.
        * **AC4:** Tras un registro exitoso, soy automáticamente autenticado y redirigido a una página de bienvenida o a mi panel de control.
        * **AC5:** (Opcional MVP, pero buena práctica) Recibo un correo de bienvenida.
    * **HU4.2:** Como usuario con cuenta, quiero poder iniciar sesión de forma segura utilizando mi correo electrónico y contraseña.
        * **AC1:** Hay un enlace/botón "Iniciar Sesión" visible y accesible.
        * **AC2:** El formulario de inicio de sesión solicita correo electrónico y contraseña.
        * **AC3:** Si las credenciales son correctas, soy autenticado y redirigido a mi panel de control o a la página anterior.
        * **AC4:** Si las credenciales son incorrectas, se muestra un mensaje de error claro sin revelar si es el usuario o la contraseña lo incorrecto.
    * **HU4.3:** Como usuario autenticado, quiero poder cerrar mi sesión.
        * **AC1:** Cuando estoy autenticado, hay una opción visible (ej. en un menú de usuario) para "Cerrar Sesión".
        * **AC2:** Al hacer clic en "Cerrar Sesión", mi sesión actual se invalida y soy redirigido a la página de inicio o de login.
    * **HU4.4:** Como cliente registrado, quiero poder ver y actualizar la información básica de mi perfil (nombre, correo electrónico).
        * **AC1:** Dentro de mi cuenta, existe una sección accesible para editar mi perfil.
        * **AC2:** Puedo ver mi nombre y correo electrónico actuales en un formulario.
        * **AC3:** Puedo modificar estos campos y guardar los cambios.
        * **AC4:** Al guardar, la información se actualiza y veo un mensaje de confirmación.
    * **HU4.5:** Como cliente registrado, quiero poder cambiar mi contraseña de forma segura.
        * **AC1:** En la configuración de mi cuenta, hay una opción para cambiar la contraseña.
        * **AC2:** El formulario para cambiar contraseña solicita: mi contraseña actual, la nueva contraseña y la confirmación de la nueva contraseña.
        * **AC3:** El sistema valida que la contraseña actual sea correcta.
        * **AC4:** El sistema valida que la nueva contraseña y su confirmación coincidan y cumplan los requisitos de seguridad.
        * **AC5:** Al cambiarla exitosamente, recibo una confirmación y mi nueva contraseña está activa.

**Epic 5: Gestión de Pedidos (Cliente)**

* **Rol:** Cliente Registrado
    * **HU5.1:** Como cliente registrado, quiero poder acceder a un historial de todos los pedidos que he realizado anteriormente.
        * **AC1:** En el panel de mi cuenta, existe una sección "Mis Pedidos" o "Historial de Pedidos".
        * **AC2:** Esta sección muestra una lista de mis pedidos, incluyendo al menos: número de pedido, fecha de realización, monto total y estado principal del pedido (ej. "Procesando", "Enviado").
        * **AC3:** Si tengo muchos pedidos, la lista está paginada.
    * **HU5.2:** Como cliente registrado, quiero poder ver los detalles completos de un pedido específico de mi historial (incluyendo productos, cantidades, precios pagados, dirección de envío, estado del pedido).
        * **AC1:** Cada pedido en la lista del historial tiene un enlace o botón para "Ver Detalles".
        * **AC2:** Al acceder al detalle, se muestra: información del pedido (número, fecha, estado), lista de productos comprados (con nombre, cantidad, precio unitario, subtotal por ítem), dirección de envío utilizada, dirección de facturación utilizada, método de pago (general, no detalles sensibles) y el desglose del total (subtotal, envío, total).

**Epic 6: Gestión de Libreta de Direcciones (Cliente)**

* **Rol:** Cliente Registrado
    * **HU6.1:** Como cliente registrado, quiero poder añadir una nueva dirección (de envío o facturación) a mi libreta personal para usarla en futuras compras.
        * **AC1:** En la sección "Mis Direcciones" de mi cuenta, hay un botón "Añadir Nueva Dirección".
        * **AC2:** Se presenta un formulario con todos los campos necesarios para una dirección completa.
        * **AC3:** Al guardar, la nueva dirección aparece en mi lista de direcciones guardadas.
    * **HU6.2:** Como cliente registrado, quiero poder ver una lista de todas mis direcciones guardadas.
        * **AC1:** La sección "Mis Direcciones" muestra todas las direcciones que he añadido, indicando si alguna es por defecto para envío o facturación.
    * **HU6.3:** Como cliente registrado, quiero poder editar los detalles de una dirección existente en mi libreta.
        * **AC1:** Cada dirección listada tiene una opción "Editar".
        * **AC2:** Al seleccionar "Editar", se abre un formulario pre-cargado con los datos de esa dirección, permitiéndome modificarlos.
        * **AC3:** Los cambios guardados se reflejan en la lista de direcciones.
    * **HU6.4:** Como cliente registrado, quiero poder eliminar una dirección de mi libreta.
        * **AC1:** Cada dirección listada (que no sea la única y/o por defecto, o con manejo especial) tiene una opción "Eliminar".
        * **AC2:** Se solicita una confirmación antes de la eliminación definitiva.
        * **AC3:** Una vez confirmada, la dirección se elimina de mi lista.
    * **HU6.5:** Como cliente registrado, quiero poder marcar una dirección como mi dirección de envío por defecto.
        * **AC1:** En la lista de direcciones, o al editar una dirección, existe la opción de establecerla como "Dirección de envío por defecto".
        * **AC2:** Solo una dirección puede ser la de envío por defecto a la vez.
        * **AC3:** La dirección marcada como por defecto se preselecciona durante el proceso de checkout.
    * **HU6.6:** Como cliente registrado, quiero poder marcar una dirección como mi dirección de facturación por defecto.
        * **AC1:** En la lista de direcciones, o al editar una dirección, existe la opción de establecerla como "Dirección de facturación por defecto".
        * **AC2:** Solo una dirección puede ser la de facturación por defecto a la vez.
        * **AC3:** La dirección marcada como por defecto se preselecciona durante el checkout si no se usa la misma de envío.

**Epic 7: Administración de la Tienda (MVP - Con UI básica para Productos y Categorías)**

* **Rol:** Administrador
    * **HU7.0:** Como administrador, quiero poder iniciar sesión en un panel de administración seguro para acceder a las funciones de gestión de la tienda.
        * **AC1:** El administrador inicia sesión con sus credenciales de usuario (que tiene el flag `is_admin`=true).
        * **AC2:** Tras iniciar sesión, el administrador es redirigido a un panel de administración o tiene acceso a un menú de administración.
        * **AC3:** Usuarios no administradores no pueden acceder a las URLs/secciones del panel de administración.
    * **Gestión de Productos (UI):**
        * **HU7.1:** Como administrador, quiero ver una lista paginada de todos los productos en el panel de administración, mostrando al menos imagen miniatura, nombre, SKU, precio, stock actual y estado (activo/inactivo), para poder gestionarlos.
            * **AC1:** La sección "Productos" del panel de admin muestra una tabla con los productos y la información especificada.
            * **AC2:** La lista de productos es paginada.
            * **AC3:** Cada fila de producto tiene acciones rápidas para "Editar" y "Eliminar".
            * **AC4:** Hay un botón "Añadir Producto".
        * **HU7.2:** Como administrador, quiero un botón para "Añadir Nuevo Producto" que me lleve a un formulario donde pueda ingresar toda la información del producto (nombre, descripción, precio MXN, SKU, cantidad en stock, categoría, marca, tipo de movimiento) y subir su imagen principal.
            * **AC1:** El formulario contiene campos para todos los atributos del producto mencionados.
            * **AC2:** Incluye un control para subir una imagen (validación de tipo/tamaño en backend).
            * **AC3:** Las categorías se listan en un selector/dropdown.
            * **AC4:** Se aplican validaciones de datos (campos requeridos, formato de precio, SKU único si se ingresa).
            * **AC5:** Al guardar, el nuevo producto se crea, su imagen se procesa y se muestra un mensaje de éxito.
        * **HU7.3:** Como administrador, quiero poder hacer clic en un producto de la lista para acceder a un formulario donde pueda editar toda su información existente, incluyendo la posibilidad de cambiar o eliminar la imagen actual y subir una nueva.
            * **AC1:** El formulario de edición se precarga con los datos actuales del producto.
            * **AC2:** Todos los campos, incluida la imagen (mostrar actual, opción de reemplazar o eliminar), son editables.
            * **AC3:** Al guardar, los cambios se aplican al producto y se muestra un mensaje de éxito.
        * **HU7.4:** Como administrador, quiero poder cambiar la cantidad de stock de un producto directamente, ya sea desde la lista (edición rápida) o desde el formulario de edición del producto.
            * **AC1:** El campo de stock es editable y numérico.
            * **AC2:** El nuevo valor de stock se guarda correctamente.
        * **HU7.5:** Como administrador, quiero poder cambiar el estado de un producto entre "Activo" e "Inactivo" para controlar su visibilidad en la tienda online.
            * **AC1:** Existe un control (ej. switch, botón) para cambiar el estado "Activo/Inactivo" de un producto, visible en la lista o en el formulario de edición.
            * **AC2:** Al cambiar el estado, este se actualiza inmediatamente.
            * **AC3:** Los productos inactivos no son visibles para los clientes en la tienda.
        * **HU7.6:** Como administrador, quiero poder eliminar un producto del catálogo (borrado lógico).
            * **AC1:** Existe una opción "Eliminar" para cada producto.
            * **AC2:** Se muestra un mensaje de confirmación antes de proceder con la eliminación.
            * **AC3:** Al confirmar, el producto se marca como eliminado (soft delete) y no aparece en la tienda pública. (Opcional MVP: el producto ya no aparece en la lista principal de admin o se mueve a una sección de "papelera").
    * **Gestión de Categorías (UI - Básica):**
        * **HU7.7:** Como administrador, quiero ver una lista de todas las categorías de productos existentes.
            * **AC1:** La sección "Categorías" del panel de admin muestra una tabla/lista con el nombre de cada categoría.
            * **AC2:** Cada fila tiene acciones para "Editar" y "Eliminar".
            * **AC3:** Hay un botón "Añadir Categoría".
        * **HU7.8:** Como administrador, quiero un botón para "Añadir Nueva Categoría" que me lleve a un formulario donde pueda ingresar el nombre, descripción y, opcionalmente, subir una imagen para la categoría.
            * **AC1:** El formulario contiene campos para nombre (obligatorio), descripción (opcional) e imagen (opcional).
            * **AC2:** Al guardar, la nueva categoría se crea y se muestra un mensaje de éxito.
        * **HU7.9:** Como administrador, quiero poder seleccionar una categoría de la lista para editar su nombre, descripción e imagen.
            * **AC1:** El formulario de edición se precarga con los datos actuales de la categoría.
            * **AC2:** Al guardar, los cambios se aplican a la categoría.
        * **HU7.10:** Como administrador, quiero poder eliminar una categoría.
            * **AC1:** Existe una opción "Eliminar" para cada categoría.
            * **AC2:** Se muestra un mensaje de confirmación.
            * **AC3:** (MVP) Se impide la eliminación si la categoría tiene productos asociados. Se muestra un mensaje informativo.
    * **Gestión de Pedidos (UI - Visualización y cambio de estado básico):**
        * **HU7.11:** Como administrador, quiero ver una lista paginada de todos los pedidos realizados, mostrando número de pedido, fecha, nombre del cliente (o email si es invitado), monto total y estado actual.
            * **AC1:** La sección "Pedidos" del panel de admin muestra una tabla con los pedidos y la información especificada.
            * **AC2:** La lista de pedidos es paginada.
            * **AC3:** Cada fila de pedido tiene una acción para "Ver Detalles".
        * **HU7.12:** Como administrador, quiero poder hacer clic en un pedido de la lista para ver sus detalles completos (productos, cantidades, precios, información de envío y facturación, estado de pago).
            * **AC1:** Al acceder al detalle, se muestra toda la información relevante del pedido, incluyendo los ítems.
        * **HU7.13:** Como administrador, quiero poder cambiar el estado de un pedido (ej. de "Procesando" a "Enviado") desde la vista de detalle del pedido.
            * **AC1:** En la vista de detalle del pedido, hay un control (ej. dropdown) para seleccionar un nuevo estado del pedido de una lista predefinida.
            * **AC2:** Al seleccionar un nuevo estado y guardar, el estado del pedido se actualiza.
            * **AC3:** (Opcional MVP) El cliente recibe una notificación por correo electrónico si el estado cambia a "Enviado".

---
