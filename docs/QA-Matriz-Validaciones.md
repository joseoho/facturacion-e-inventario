# Matriz de validaciones y pruebas de seguridad

**Aplicación:** Facturación e Inventario (Laravel 11 + MySQL/MariaDB)
**Fecha:** 2026-10-27
**Alcance:** formularios web autenticados y de acceso.
**Leyenda:** ✅ implementado · ⚠️ parcial · ❌ ausente

---

## 0. Hallazgo crítico corregido

### 0.1 `NULL` en `clientes.limite_credito` y `clientes.dias_credito`

- **Síntoma:** al enviar el formulario de cliente con los campos de crédito vacíos,
  MySQL responde `Column 'limite_credito' cannot be null` (o `dias_credito`).
- **Causa:** ambos campos son opcionales (`nullable`) en la validación, pero las
  columnas son `NOT NULL DEFAULT 0`. `ConvertEmptyStringsToNull` los convierte en
  `null` y Eloquent los inserta explícitamente.
- **Corrección:** normalización a `0` en `store` y `update` de
  `ClienteController`; longitudes alineadas a la BD (`nombre` 200, `email` 100,
  `contacto` 100).
- **Pruebas:** `tests/Feature/ClientesTest.php` (alta y edición con crédito vacío,
  nombre de 201 caracteres, vendedor sin permiso).
- **Commit:** `36964b5`.

---

## 1. Matrices por formulario

### 1.1 Login

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `email` | string(255) | `type=email`, `required`, `autocomplete=email`; sin `maxlength` ⚠️ | `required\|string\|email`; las credenciales añaden `activo=1` | CSRF ✅; mensaje genérico ✅; **sin rate limiting** ❌; enumeración evitada ✅ |
| `password` | string | `type=password`, `required`, `autocomplete=current-password`; sin medidor ni ver/ocultar ⚠️ | `required\|string` | Hash bcrypt ✅; sin throttle ❌; sin 2FA ❌ |
| `remember` | bool | checkbox | no se valida | Cookie persistente; regeneración de sesión al entrar ✅ |

### 1.2 Recuperación y cambio de contraseña

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `email` (solicitud) | string | `type=email`, `required` | `required\|email`; respuesta genérica exista o no el correo | CSRF ✅; token con caducidad (60 min) ✅; sin throttle por IP ❌ |
| `token` | string | oculto en la URL | `required`; se compara con hash en BD | Token de un solo uso ✅; en local el correo se escribe en `laravel.log` ⚠️ |
| `password` (reset) | string | `type=password`, `required`; sin `minlength` ni medidor ⚠️ | `required\|confirmed\|min:8` | bcrypt ✅; sin bloqueo tras intentos ❌ |
| `current_password` | string | `type=password`, `required` | `required\|current_password` | Sin rate limiting: permite probar la clave actual por fuerza bruta ❌ |
| `password` (cambio) | string | `required`; sin indicador de fuerza ⚠️ | `required\|confirmed\|Password::min(8)->letters()->numbers()` | bcrypt ✅; sin historial de contraseñas ❌ |

### 1.3 Usuarios (solo admin)

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `name` | string(255) | `required`, `maxlength=255` ✅ | `required\|string\|max:255` | Autorización admin ✅; escape XSS ✅; cambios sin auditoría ⚠️ |
| `email` | string(255) | `type=email`, `required`, `maxlength=255` ✅ | `required\|email\|max:255\|unique` | CSRF ✅; unicidad app + BD ✅ |
| `role` | enum | select `admin/vendedor` | `required\|in:admin,vendedor` | No puede quitarse el rol admin ✅; último admin activo protegido ✅ |
| `password` | string | `required` al crear; opcional al editar; sin medidor ⚠️ | `Password::min(8)->letters()->numbers()\|confirmed` | bcrypt ✅; admin puede resetear sin clave actual ⚠️ |
| `activo` | bool | checkbox | `boolean` | No puede desactivarse a sí mismo ✅; desactivar cierra la sesión ✅ |

### 1.4 Clientes

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `nombre` | string(200) | `required`; sin `maxlength` ⚠️ | `required\|string\|max:200` | Escape XSS ✅; admin-only ✅ |
| `documento` | string(50) | `required`; sin máscara/RIF ⚠️ | `required\|string\|max:50\|unique` | Unicidad BD ✅; sin validación de formato ❌ |
| `tipo_documento` | string(20) | select `J,V,G,E,CI,OTRO`, `required` | `required\|in:J,V,G,E,CI,OTRO` | Lista blanca ✅ |
| `direccion` | string(500) | texto libre | `nullable\|string\|max:500` | Escape XSS ✅ |
| `telefono` | string(20) | texto; sin máscara ⚠️ | `nullable\|string\|max:20` | Sin validación de formato ❌ |
| `email` | string(100) | `type=email` | `nullable\|email\|max:100\|unique` | Unicidad solo app (la BD no tiene índice único) ⚠️ |
| `contacto` | string(100) | texto | `nullable\|string\|max:100` | Escape XSS ✅ |
| `tipo_cliente` | enum | select, `required` | `required\|in:minorista,mayorista,corporativo` | Lista blanca ✅ |
| `limite_credito` | decimal(15,2) | `type=number step=0.01`; sin `min` ⚠️ | `nullable\|numeric\|min:0\|max:99999999.99`; **null → 0** ✅ | Corrección del bug `NOT NULL` ✅ |
| `dias_credito` | integer | `type=number`; sin `min` ⚠️ | `nullable\|integer\|min:0\|max:365`; **null → 0** ✅ | Corrección del bug `NOT NULL` ✅ |
| `activo` | bool | checkbox | `boolean` | Borrado bloqueado con facturas ✅ |
| `notas` | text | textarea | `nullable\|string\|max:1000` | Escape XSS ✅ |

### 1.5 Productos

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `sku` | string(50) | `required`; `maxlength` ⚠️ | `required\|string\|max:50\|unique` | Unicidad BD ✅ |
| `nombre` | string(200) | `required`; sin `maxlength` ⚠️ | `required\|string\|max:200` | Escape XSS ✅ |
| `descripcion` | text | textarea | `nullable\|string` | Escape XSS ✅ |
| `precio_kg_usd` | decimal(15,4) | `type=number`, `min=0`, `step=0.0001` | `required\|numeric\|min:0`; **sin máximo** ❌ | Riesgo de overflow/truncamiento ⚠️ |
| `stock_kg` | decimal(15,3) | `type=number`, `min=0`, `step=0.001` | `required\|numeric\|min:0`; **sin máximo** ❌ | Riesgo de overflow ⚠️ |
| `categoria_id` | FK | select, `required` | `required\|exists:categorias,id` | FK BD ✅ |
| `iva_porcentaje` | decimal(5,2) | `min=0`, `max=100` | `required\|numeric\|min:0\|max:100` | Lista numérica ✅ |
| `stock_minimo` | decimal(15,3) | `min=0` | `nullable\|numeric\|min:0`; null → 0 ✅ | — |
| `imagen` | archivo | `accept=image/*`; sin límite visible ⚠️ | `nullable\|image\|mimes:jpeg,png,jpg,gif\|max:2048` | MIME real con `image` ✅; sin antivirus ❌; servida desde `public/` ⚠️ |
| `activo` | bool | checkbox | `boolean` | Soft delete ✅ |
| `moneda_id`, `precio_kg_local`, `tasa_cambio_id` | FK/decimal | selects y número | `nullable\|exists`; solo crea el precio si llegan los tres | No valida que la tasa pertenezca a la moneda ❌ |

### 1.6 Precios por moneda (producto)

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `moneda_id` | FK | select, `required` | `required\|exists:monedas,id` | Admin-only ✅ |
| `tasa_cambio_id` | FK | select, `required` | `required\|exists:tasas_cambio,id` | No valida moneda ↔ tasa ❌ |
| `precio_kg` | decimal(15,4) | `min=0`, `step=0.0001` | `required\|numeric\|min:0`; sin máximo ❌ | Duplicado producto+moneda devuelve 500 (no se valida) ❌ |

### 1.7 Categorías

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `nombre` | string(100) | `required` | `required\|string\|max:100\|unique` | Único en BD ✅; admin-only ✅ |
| `descripcion` | text | textarea | `nullable\|string\|max:500` | Escape XSS ✅ |
| `activo` | bool | checkbox | `boolean` | No elimina con productos ✅ |

### 1.8 Monedas

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `codigo` | string(3) | `required`; sin `maxlength=3` ⚠️ | `required\|string\|max:3\|unique`; se guarda en mayúsculas | Único en BD ✅ |
| `nombre` | string(50) | `required`; sin `maxlength` ⚠️ | `required\|string\|max:100` → **desajuste con BD (50)** ❌ | Escape XSS ✅ |
| `simbolo` | string(5) | `required`; sin `maxlength` ⚠️ | `required\|string\|max:10` → **desajuste con BD (5)** ❌ | Escape XSS ✅ |
| `es_base` | bool | checkbox | `boolean` | Una sola base ✅; único base no desactivable ✅ |
| `activo` | bool | checkbox | `boolean` | No elimina con facturas/precios ✅ |

### 1.9 Tasas de cambio

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `moneda_id` | FK | select, `required` | `required\|exists:monedas,id` | Admin-only ✅; no exige moneda activa ⚠️ |
| `tasa` | decimal(15,6) | `type=number`, `step=0.000001`, `min` | `required\|numeric\|min:0.000001\|max:999999.999999` | Sin overflow ✅ |
| `fecha` | date | `type=date`; sin `max=hoy` ⚠️ | `required\|date\|before_or_equal:now` | Único moneda+fecha, upsert ✅ |
| `per_page` (filtro) | integer | select | **sin máximo** ❌ | DoS por paginación gigante ⚠️ |
| `sort`, `direction` | string | selects | `sort` con lista blanca ✅; `direction` la valida el Query Builder ✅ | — |

### 1.10 Facturas

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `cliente_id` | FK | select, validación Alpine | `required\|exists:clientes,id` | No exige cliente activo ⚠️ |
| `moneda_id` | FK | select, `required` | `required\|exists:monedas,id` | **Se ignora para precios**; no coincide necesariamente con `moneda_pago` ❌ |
| `moneda_pago` | string(10) | botones USD/BS/COP | `required\|in:USD,COP,BS,VES,EUR` | Lista blanca ✅; la UI no ofrece EUR ⚠️ |
| `fecha_emision` | date | `type=date` | **no se envía**; el backend usa `now()` ❌ | El campo visible no tiene efecto ⚠️ |
| `productos[]` | array | al menos 1; validación Alpine | `required\|array\|min:1`; **sin máximo** ❌ | Payload grande ⚠️ |
| `productos[].producto_id` | FK | búsqueda y selección | `required\|exists:productos,id` | Se exige activo y stock en el servicio ✅ |
| `productos[].cantidad_kg` | decimal(15,3) | límite por stock en JS | `required\|numeric\|min:0.001\|max:9999.999` | Se bloquea el producto y se descuenta stock ✅ |
| `productos[].precio_kg` | decimal(15,4) | se calcula en pantalla | `required\|numeric\|min:0`; **el backend impone el precio oficial** ✅ | Anti-tampering ✅; se registra en log ✅ |

### 1.11 Egresos

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `categoria_egreso_id` | FK | select, `required` | `required\|exists:categorias_egreso,id`; se exige activa en el servicio | Admin-only ✅ |
| `proveedor_nombre` | string(200) | texto | `nullable\|string\|max:200` | Escape XSS ✅; texto libre (decisión) |
| `concepto` | text | `required`; sin `maxlength` visible ⚠️ | `required\|string\|max:1000` | Escape XSS ✅ |
| `moneda_id` | FK | select USD/BS/COP | `required\|exists`; solo USD/BS/COP y activa | Lista blanca ✅ |
| `monto` | decimal(12,4) | `type=number`, `min=0.0001` | `required\|numeric\|gt:0\|max:9999999999` → **excede decimal(12,4)** ❌ | Overflow/truncamiento ⚠️ |
| `fecha_egreso` | date | `type=date` | `required\|date` | — |
| `estado` | enum | select | `required\|in:pendiente,pagado` | — |
| `metodo_pago` | enum | select | `nullable\|in:efectivo,transferencia,pago_movil,zelle,otro` | Lista blanca ✅ |
| `referencia_pago` | string(100) | texto | `nullable\|string\|max:100` | Escape XSS ✅ |
| `producto_id` | FK | select condicional | `nullable\|exists`; **obligatorio si la categoría afecta inventario** ✅ | Se bloquea el producto y se suma stock ✅ |
| `cantidad_kg` | decimal(10,3) | número condicional | `nullable\|numeric\|gt:0\|max:999999`; obligatorio si afecta inventario ✅ | Stock revertido al anular ✅ |

### 1.12 Categorías de egreso

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `nombre` | string(255) | `required`; `maxlength=100` | `required\|string\|max:100\|unique` | **La BD no tiene índice único** ⚠️; admin-only ✅ |
| `descripcion` | string(255) | texto; `maxlength=500` | `nullable\|string\|max:500` → **desajuste con BD (255)** ❌ | Escape XSS ✅ |
| `afecta_inventario` | bool | checkbox | `boolean` | Define stock; sin auditoría del cambio ⚠️ |
| `activo` | bool | checkbox | `boolean` | No elimina con egresos ✅ |

### 1.13 Búsquedas y filtros (GET)

| Campo | Tipo | Validación Frontend | Validación Backend | Validaciones de Seguridad |
|---|---|---|---|---|
| `q` / `search` | string | texto | **sin `max`** ❌ | LIKE parametrizado ✅; comodines `%` permitidos; posible consulta costosa ⚠️ |
| `desde`, `hasta` | date | `type=date` | `$request->date()` sin validar ❌ | Una fecha inválida puede lanzar excepción (500) ❌ |
| `estado`, `moneda_id`, `categoria_id`, etc. | string/FK | selects | `filled` + `where`; sin `exists` en filtros ⚠️ | Sin impacto de inyección; valores inválidos devuelven vacío |
| `per_page` (tasas) | integer | select | **sin máximo** ❌ | DoS por paginación ⚠️ |

---

## 2. Validaciones globales del formulario

| Control | Estado | Detalle |
|---|---|---|
| CSRF | ✅ | Middleware web en todos los POST/PUT/DELETE; formularios con `@csrf`; fetch con `X-CSRF-TOKEN`. |
| Autenticación | ✅ | Rutas bajo `auth`; sesión regenerada al iniciar. |
| Autorización | ✅ | `role:admin` + Policies; vendedor redirigido/403. |
| Rate limiting | ❌ | Sin `throttle` en login, reset ni cambio de contraseña; fuerza bruta viable. |
| SQL injection | ✅ | Eloquent/Query Builder con bindings; SQL crudo parametrizado; `orderBy` con lista blanca. |
| XSS | ✅ | Blade `{{ }}` escapa; sin `{!! !!}` con datos de usuario; JSON codificado. |
| Mass assignment | ✅ | `$fillable` + `validated()`; no se pasa `$request->all()`. |
| Archivos | ⚠️ | Solo imagen de producto: `image`, MIME real, 2 MB; sin antivirus ni análisis de polyglot; almacenamiento público. |
| Códigos HTTP | ⚠️ | 422 en validación JSON, 403 en autorización, 419 en CSRF; formularios web redirigen con errores; algunos fallos de BD terminan en 500. |
| Doble envío | ⚠️ | Facturas deshabilitan el botón; clientes/productos/egresos/usuarios no; sin claves de idempotencia. |
| Concurrencia | ✅ | Transacciones, `lockForUpdate` y numeración con bloqueo en facturas/egresos. |
| Sesión y cookies | ✅ | Driver database, `HttpOnly`, `SameSite=Lax`; `Secure` solo en producción. |
| Cabeceras de seguridad | ❌ | Sin CSP, HSTS, `X-Content-Type-Options`, `Referrer-Policy` ni `Permissions-Policy`. |
| Enumeración de usuarios | ✅ | Login y reset con respuesta genérica; registro público deshabilitado. |
| Contraseñas | ✅ | bcrypt; mínimo 8 con letras y números al crear/cambiar. |
| Debug en producción | ✅ | `APP_DEBUG=false` documentado; `.env` fuera del repo. |
| Auditoría | ⚠️ | Facturas y egresos registran en log; cambios de usuarios y precios no. |
| Backups | ❌ | Fuera de la aplicación; documentado como tarea operativa. |

---

## 3. Casos de prueba recomendados

| ID | Caso | Entrada | Resultado esperado |
|---|---|---|---|
| AUTH-01 | Login válido | credenciales correctas | 302 al dashboard, sesión regenerada |
| AUTH-02 | Clave incorrecta | clave errónea | Error genérico, sin revelar existencia |
| AUTH-03 | Usuario inactivo | `activo=0` | Rechazo de acceso |
| AUTH-04 | Fuerza bruta | 10 intentos seguidos | 429 (hoy no ocurre) |
| SEC-01 | CSRF ausente | POST sin `_token` | 419 |
| SEC-02 | SQLi en búsqueda | `' OR 1=1--` | Sin cambio de resultado; consulta parametrizada |
| SEC-03 | XSS almacenado | `<script>alert(1)</script>` en nombre/notas | Se muestra escapado |
| SEC-04 | Mass assignment | `role=admin` en POST de cliente | Ignorado por `$fillable` |
| SEC-05 | IDOR vertical | vendedor a `/monedas` | Redirección/403 |
| CLI-01 | Crédito vacío | `limite_credito=[]`, `dias_credito=[]` | Se guarda 0 (bug corregido) |
| CLI-02 | Nombre 201 caracteres | 201 `A` | 422 |
| CLI-03 | Documento duplicado | mismo documento | 422 |
| PROD-01 | Archivo PHP disfrazado | `.php` con MIME imagen | Rechazo |
| PROD-02 | Imagen 3 MB | archivo > 2048 KB | 422 |
| PROD-03 | Precio gigante | `1e30` | 422 (hoy no se valida máximo) |
| FAC-01 | Factura USD | 1 producto, stock | 201, stock descontado |
| FAC-02 | BS sin tasa | moneda BS sin tasa | 422 con mensaje |
| FAC-03 | Stock insuficiente | cantidad > stock | 422 y rollback |
| FAC-04 | Precio manipulado | `precio_kg=0.01` | Se guarda el precio oficial |
| FAC-05 | Doble clic | dos POST simultáneos | Dos facturas (sin idempotencia) |
| EGR-01 | Inventario sin producto | categoría afecta, sin producto | 422 |
| EGR-02 | Monto fuera de rango | `9999999999` | 500 por overflow (hoy) |
| EGR-03 | Anular con stock vendido | stock < cantidad | 422 con mensaje |
| EGR-04 | Anular dos veces | segundo POST | 422 |
| GLOB-01 | Fecha inválida en filtro | `?desde=abc` | 500 (hoy) |
| GLOB-02 | `per_page=100000` | consulta de tasas | Respuesta gigante (hoy) |

---

## 4. Hallazgos priorizados

### P0

1. **`NULL` en crédito de clientes** — corregido (`36964b5`).
2. **Sin rate limiting** en login, recuperación y cambio de contraseña.
3. **Desajustes de longitud/tipo con la BD**: `monedas.nombre` (100 vs 50),
   `monedas.simbolo` (10 vs 5), `categorias_egreso.descripcion` (500 vs 255),
   `egresos.monto` (máximo de validación mayor que `decimal(12,4)`),
   `productos.precio_kg_usd` y `stock_kg` sin máximo.
4. **Precio duplicado por producto/moneda** devuelve 500 en lugar de 422.

### P1

5. `moneda_id` y `moneda_pago` pueden no coincidir; el precio se calcula con el
   segundo y la factura guarda el primero.
6. `fecha_emision` visible en el formulario pero ignorada por el backend.
7. Fechas de filtros sin validar (`$request->date()` puede lanzar 500).
8. `per_page` sin límite en tasas.
9. Sin cabeceras de seguridad (CSP, HSTS, nosniff, Referrer-Policy).
10. Sin protección de doble envío/idempotencia en la mayoría de formularios.
11. Archivos servidos desde `public/` sin antivirus ni control de acceso.
12. Unicidad solo en aplicación para `clientes.email` y `categorias_egreso.nombre`.

### P2

13. Mensajes de autenticación en inglés (no hay carpeta `lang/es`).
14. Accesibilidad: falta `aria-describedby`, `aria-live` en errores y `maxlength`.
15. Sin máscaras/regex para documento, teléfono y código de moneda.
16. Posible CSV injection en la exportación de tasas.
17. Sin auditoría de cambios de usuarios, roles y precios.

---

## 5. Cómo ejecutar las pruebas

```bash
php artisan test --compact
composer lint
```

Los casos de seguridad manuales (CSRF, XSS, SQLi, upload, brute force) deben
probarse contra una instalación de staging con `APP_DEBUG=false`.
