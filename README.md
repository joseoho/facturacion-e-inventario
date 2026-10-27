# Facturación e Inventario

Sistema interno de facturación multi-moneda y control de inventario (Laravel 11 + MySQL 8).
No emite facturación electrónica ni se integra con entes externos.

## Stack

| Componente | Versión |
|---|---|
| PHP | 8.2+ |
| Laravel | 11.x |
| MySQL | 8.0 (InnoDB) |
| Frontend | Vite 8 + Bootstrap 5 + Alpine.js |
| Tests | PHPUnit 10 + Laravel TestCase |
| Estilo | Laravel Pint |

## Módulos

- **Facturación**: facturas en USD / BS / COP / EUR, IVA por producto, tasa de cambio
  congelada por factura, anulación con reposición de stock, pago con fecha.
- **Inventario**: stock en Kg con 3 decimales, stock mínimo, movimientos de entrada/salida.
- **Egresos**: registro plano (un egreso = un concepto) con categoría, proveedor en
  texto libre, moneda de pago (USD/BS/COP), monto único, método de pago y anulación.
  Si la categoría afecta inventario, suma stock al producto y al anular lo revierte
  (bloquea la anulación si el stock ya fue vendido).
- **Categorías de egreso**: catálogo administrable.
- **Usuarios** (solo admin): alta, asignación de rol, activar/desactivar y reseteo de contraseña.
  Desactivar a un usuario invalida su sesión activa.
- **Tasas de cambio**: carga diaria, histórico, exportación CSV, actualización masiva de precios.
- **Reportes**: inventario, stock bajo, ventas diarias, dashboard con métricas.
- **Usuarios**: roles `admin` y `vendedor`, con policies por módulo.

## Requisitos

- PHP 8.2 con extensiones `pdo_mysql`, `mbstring`, `bcmath`, `gd`, `zip`, `intl`
- Composer 2
- Node.js 20+ y npm
- MySQL 8

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate

# Crear la base de datos y configurar credenciales en .env
php artisan migrate --seed

npm install
npm run build        # o `npm run dev` para desarrollo
php artisan storage:link
```

Credenciales del seeder: `admin@facturador.com` / `Admin123!`.
Cámbialas al entrar desde **Sistema → Cambiar contraseña** (menú lateral).

## Instalación automática en Windows (XAMPP)

**Requisitos previos:** XAMPP 8.2+ (PHP y MySQL) y Node.js 20+ (npm).
Si falta XAMPP, el instalador se detiene; si falta Node, intenta instalarlo con `winget`.

Doble clic en **`instalar.bat`**. El script:

1. Pide permisos de administrador (necesarios para `hosts` y Apache).
2. Descarga el proyecto si no está (por `git clone` o ZIP si no hay Git), siempre en
   `C:\xampp\htdocs\facturacion-inventario`.
3. Instala Composer si falta.
4. Crea la base `facturacion_db`, ejecuta migraciones y un seeder idempotente
   (`InstalacionSeeder`): crea lo que falte y **no borra datos** existentes.
5. Compila los assets con Vite (`npm run build`; intenta instalar Node LTS con `winget` si falta).
6. Configura el VirtualHost **sin borrar los existentes**: respalda
   `httpd.conf` y `httpd-vhosts.conf` antes de tocarlos y solo agrega lo que falta.
7. Abre `http://facturacion-inventario.test`.

Al terminar, deja un acceso en la carpeta de Inicio de Windows que levanta Apache y
MySQL al iniciar sesión (si ya están corriendo, no hace nada). Deja rastro en
`storage/logs/xampp-autostart.log`.

Para quitar ese arranque automático:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\autostart.ps1 -Remove
```

> Arranca al **iniciar sesión**, no antes del login. Si necesitas que funcione al
> encender el equipo sin iniciar sesión, habría que instalar Apache y MySQL como
> servicios de Windows.

Diagnóstico sin instalar nada:

```bat
instalar.bat --check
```

Solo configurar el vhost (no instala la aplicación). Ejecutar en PowerShell **como administrador**:

```powershell
powershell -ExecutionPolicy Bypass -File "C:\xampp\htdocs\facturacion-inventario\scripts\setup-vhost.ps1" -AppPath "C:\xampp\htdocs\facturacion-inventario"
```

Si tu MySQL tiene contraseña, edita `DB_PASS` al inicio de `instalar.bat`.

## Solución de problemas

### El navegador muestra `DNS_PROBE_FINISHED_NXDOMAIN`

El nombre `facturacion-inventario.test` no resuelve. Ejecuta el comando de vhost de
arriba (como administrador) y luego:

```powershell
ipconfig /flushdns
```

Comprueba que exista esta línea en `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 facturacion-inventario.test
```

### El navegador muestra `Vite manifest not found`

Faltan los assets compilados. El instalador ejecuta **`npm run build`** (no
`npm run dev`). Si ese paso falló, entra a la carpeta del proyecto y ejecuta:

```bash
npm install
npm run build
```

Al terminar debe existir `public/build/manifest.json`. `npm run dev` es solo para
desarrollo: levanta un servidor que debe quedar abierto y no genera el manifest de
producción.

### Las imágenes de productos no se ven (404)

Falta el enlace `public/storage`. En Windows, si `public\storage` existe como
carpeta vacía, `php artisan storage:link` falla y las imágenes subidas no se
sirven. Ejecuta:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\storage-link.ps1
```

Crea un *junction* `public\storage -> storage\app\public` sin necesidad de
administrador. El instalador ya ejecuta este paso automáticamente.

### Error 419 «Page Expired» al iniciar sesión

Es un problema de **CSRF/sesión**, no del código.

**Causa más frecuente en instalaciones nuevas:** quedó una caché de configuración
de una ejecución anterior (por ejemplo, con otro `APP_KEY` o sin la tabla
`sessions`). En ese caso, `php artisan optimize:clear` es lo que lo resuelve.

Revísalo en este orden:

1. Limpia la caché de configuración:

   ```bash
   php artisan optimize:clear
   ```

2. Revisa el `.env` (para HTTP local debe verse así):

   ```
   APP_KEY=base64:...
   SESSION_DRIVER=database
   SESSION_ENCRYPT=false
   SESSION_DOMAIN=null
   ```

   Si tienes `SESSION_SECURE_COOKIE=true` o `SESSION_ENCRYPT=true`, el navegador
   no envía la cookie por HTTP y el POST llega sin sesión. Ponlos en `false` (o
   elimínalos) y vuelve a ejecutar `php artisan optimize:clear`.

3. Si `APP_KEY` está vacía, genera una nueva:

   ```bash
   php artisan key:generate
   ```

4. Entra siempre por el mismo host (`http://facturacion-inventario.test`); no
   mezcles `localhost` ni `127.0.0.1`.

5. Confirma que exista la tabla `sessions`:

   ```bash
   php artisan db:show
   ```

   Con `SESSION_DRIVER=database`, si la tabla no existe o el usuario de MySQL no
   puede escribir, la sesión no se guarda.

6. Para aislar el problema, cambia temporalmente `SESSION_DRIVER=file`, limpia la
   caché y reintenta. Si con `file` funciona, el problema está en la tabla o en
   los permisos de `sessions`.

### Error 500 o pantalla en blanco

Revisa `storage/logs/laravel.log` y confirma que `storage/` y `bootstrap/cache/`
tengan permisos de escritura.

## Respaldos

Antes de cada migración, el instalador crea `backups\AAAAMMDD_HHMMSS\` con:

- `facturacion_db.sql` (volcado completo con `mysqldump`).
- `storage\` (imágenes de productos).
- `.env` (credenciales; protégelo).

Para respaldar manualmente:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\backup.ps1
```

Restaurar:

1. Detener Apache.
2. Restaurar la base (desde `cmd`):
   ```bat
   "C:\xampp\mysql\bin\mysql.exe" -u root -p facturacion_db < backups\AAAAMMDD_HHMMSS\facturacion_db.sql
   ```
3. Copiar `backups\AAAAMMDD_HHMMSS\storage\*` a `storage\app\public`.
4. Restaurar `.env` si cambió y ejecutar `php artisan optimize:clear`.

Copia los respaldos fuera del equipo. **No uses `php artisan migrate:fresh` en
producción**: borra todas las tablas. El instalador solo ejecuta
`migrate --force`, que agrega cambios sin eliminar datos.

## Seguridad

- CSRF en todos los formularios y `X-CSRF-TOKEN` en las peticiones `fetch`.
- Rate limiting: login 5/min por email+IP; solicitud de recuperación 5/min;
  reset 10/min; cambio de contraseña 6/min.
- Autorización por rol (`admin`/`vendedor`) y Policies por recurso.
- Consultas con bindings de Eloquent y salida escapada por Blade.
- Cabeceras `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y
  `Permissions-Policy`.
- Subida de imágenes validada por MIME real y tamaño máximo de 2 MB.
- `APP_DEBUG=false` en producción y `.env` fuera del repositorio.

## Comandos

```bash
composer test          # suite completa
composer test:coverage # suite con cobertura (requiere Xdebug/PCOV)
composer lint          # verifica estilo con Pint
composer lint:fix      # corrige estilo

npm run dev            # Vite en modo desarrollo
npm run build          # build de producción

php artisan migrate --force
php artisan optimize   # config + rutas + vistas en caché
php artisan optimize:clear
```

## Tests

La suite usa una base de datos **aislada** (`facturacion_test`) definida en
`.env.testing` y `phpunit.xml`. `RefreshDatabase` recrea el esquema en cada
ejecución, así que nunca apuntes esos valores a `facturacion_db`.

```bash
php artisan test --compact
```

Cobertura actual: facturación (USD/BS, IVA, stock, precio manipulado, numeración),
anulación y pago, egresos (alta, anulación, pago, total USD), permisos por rol,
monedas y login de usuarios inactivos.

## CI

`.github/workflows/ci.yml` ejecuta en cada push/PR a `main`:

1. **tests** (matriz PHP 8.2/8.3) con servicio MySQL 8, migraciones desde cero y suite.
2. **lint** con Pint.
3. **assets** con `npm ci && npm run build`.

Se recomienda proteger `main`: exigir los tres checks antes de mergear.

## Decisiones de negocio relevantes

- **Moneda base**: la marcada con `es_base = true` (USD). No requiere tasa;
  sus precios salen de `productos.precio_kg_usd`.
- **Precio de venta**: hoy se calcula como `precio_kg_usd × tasa vigente` y el backend
  impone ese valor (ignora el precio que envía el frontend). La tabla `precios_productos`
  se mantiene para el histórico y la actualización masiva; existen monedas sin precios
  cargados (COP/EUR), por eso no se usa como fuente única todavía.
- **Tasa congelada**: cada factura guarda `tasa_cambio_id` y `tasa_cambio_usada`.
- **Numeración**: `FACT-XXXXXXXX` y `EGR-XXXXXXXX`, generadas con bloqueo de fila
  dentro de transacción y respaldadas por un índice único.

## Estructura

```
app/
  Http/Controllers/      Controladores por módulo
  Http/Requests/         Validación (StoreEgresoRequest)
  Models/                Eloquent
  Policies/              Autorización por módulo
  Services/              Lógica de negocio transaccional (EgresoService)
database/
  migrations/            Esquema reproducible
  factories/             Datos de prueba
  seeders/               Datos iniciales
resources/views/         Blade por módulo
tests/Feature/           Pruebas de integración HTTP
```

## Despliegue

Ver [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
