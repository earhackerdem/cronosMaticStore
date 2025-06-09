# Integración PayPal Sandbox - CronosMatic Store

## Descripción General

Este documento describe la implementación completa de la integración con PayPal Sandbox para el procesamiento de pagos en CronosMatic Store.

## Configuración

### Variables de Entorno

Agregar las siguientes variables al archivo `.env`:

```env
# PayPal Configuration
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=tu_client_id_aqui
PAYPAL_CLIENT_SECRET=tu_client_secret_aqui
```

### Configurar Credenciales PayPal

1. Ve a [PayPal Developer Dashboard](https://developer.paypal.com/)
2. Crea una aplicación en modo Sandbox
3. Copia el Client ID y Client Secret
4. Configura las URLs de retorno:
   - Success URL: `http://tu-dominio.com/orders/payment/success`
   - Cancel URL: `http://tu-dominio.com/orders/payment/cancel`

## API Endpoints

### 1. Verificar Configuración
```http
GET /api/v1/payments/paypal/verify-config
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "PayPal configuration verified",
    "config": {
        "mode": "sandbox",
        "client_id_configured": true,
        "client_secret_configured": true,
        "access_token_test": "success",
        "access_token_length": 2048
    }
}
```

### 2. Crear Orden PayPal
```http
POST /api/v1/payments/paypal/create-order
```

**Body:**
```json
{
    "order_id": 123
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "PayPal order created successfully",
    "data": {
        "paypal_order_id": "5O190127TN364715T",
        "approval_url": "https://www.sandbox.paypal.com/checkoutnow?token=5O190127TN364715T",
        "order_number": "ORD-123456"
    }
}
```

### 3. Capturar Pago
```http
POST /api/v1/payments/paypal/capture-order
```

**Body:**
```json
{
    "order_id": 123,
    "paypal_order_id": "5O190127TN364715T"
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "message": "Payment captured successfully",
    "data": {
        "capture_id": "8FA8BC5B1B241663Y",
        "status": "COMPLETED",
        "order_number": "ORD-123456",
        "payment_status": "paid"
    }
}
```

### 4. Simular Pago Exitoso (Solo para Testing)
```http
POST /api/v1/payments/paypal/simulate-success
```

**Body:**
```json
{
    "order_id": 123
}
```

### 5. Simular Pago Fallido (Solo para Testing)
```http
POST /api/v1/payments/paypal/simulate-failure
```

**Body:**
```json
{
    "order_id": 123
}
```

## Flujo de Pago

### Para Pagos Reales (Sandbox)

1. **Crear Orden PayPal**: El frontend llama a `create-order` con el ID de la orden
2. **Redirección**: El usuario es redirigido a PayPal usando `approval_url`
3. **Autorización**: El usuario autoriza el pago en PayPal
4. **Retorno**: PayPal redirige de vuelta con `success` o `cancel`
5. **Captura**: Si es exitoso, se llama a `capture-order` para completar el pago

### Para Testing

1. **Simulación**: Usar endpoints `simulate-success` o `simulate-failure`
2. **Verificación**: Los estados de la orden se actualizan automáticamente

## Servicios Implementados

### PayPalPaymentService

**Métodos principales:**
- `createOrder(Order $order)`: Crea orden en PayPal
- `captureOrder(string $paypalOrderId, Order $order)`: Captura pago autorizado
- `getOrderDetails(string $paypalOrderId)`: Obtiene detalles de orden PayPal
- `simulateSuccessfulPayment(Order $order)`: Simula pago exitoso
- `simulateFailedPayment(Order $order)`: Simula pago fallido

### OrderService

**Integración con PayPal:**
- Actualización automática de estados de pago
- Integración con `payment_gateway` y `payment_id`
- Manejo de transiciones de estado de orden

## Estados de Orden

### Payment Status
- `pending`: Pago pendiente
- `paid`: Pago completado exitosamente
- `failed`: Pago falló
- `refunded`: Pago reembolsado

### Order Status
- `pending_payment`: Esperando pago
- `processing`: En procesamiento
- `shipped`: Enviado
- `delivered`: Entregado
- `cancelled`: Cancelado

## Manejo de Errores

### Errores Comunes

1. **Configuración incorrecta**: Verificar credenciales PayPal
2. **Orden en estado inválido**: Solo se pueden procesar órdenes `pending_payment`
3. **Fallo de API PayPal**: Revisar logs para detalles específicos

### Logging

Todos los eventos de PayPal se registran en logs con contexto completo:
- Creación de órdenes
- Capturas de pago
- Errores de API
- Simulaciones de testing

## Testing

### Ejecutar Tests
```bash
php artisan test tests/Unit/Services/PayPalPaymentServiceTest.php
```

### Tests Incluidos
- ✅ Simulación de pagos exitosos y fallidos
- ✅ Creación y captura de órdenes PayPal
- ✅ Manejo de errores de API
- ✅ Validación de estructura de datos
- ✅ Pruebas de conectividad

## URLs de Retorno

### Success Page
- **Ruta**: `/orders/payment/success`
- **Parámetros**: `token` (PayPal Order ID), `PayerID`
- **Vista**: `Payment/Success`

### Cancel Page
- **Ruta**: `/orders/payment/cancel`
- **Parámetros**: `token` (PayPal Order ID)
- **Vista**: `Payment/Cancel`

## Notas de Seguridad

1. **Sandbox vs Production**: Cambiar `PAYPAL_MODE` a `live` para producción
2. **Credenciales**: Mantener secretas las credenciales de PayPal
3. **Validación**: Todos los endpoints validan datos de entrada
4. **Logging**: Los datos sensibles se omiten de los logs

## Criterios de Aceptación ✅

- ✅ Se puede iniciar un proceso de pago con PayPal Sandbox
- ✅ Se puede simular un pago exitoso y uno fallido
- ✅ El estado del pedido se actualiza correctamente según el resultado del pago

## Próximos Pasos

1. **Webhooks PayPal**: Implementar webhooks para confirmaciones asíncronas
2. **Refunds**: Agregar funcionalidad de reembolsos
3. **Multi-currency**: Soporte para múltiples monedas
4. **PayPal Plus**: Integración con PayPal Plus para México 
