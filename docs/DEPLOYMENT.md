# Guía de despliegue a producción

## 1. Checklist previo

- [ ] Copia de seguridad de la base de datos (`mysqldump`) y de `storage/app`.
- [ ] `.env` de producción copiado desde `.env.production.example`.
- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `APP_URL` con HTTPS.
- [ ] `APP_KEY` generada (`php artisan key:generate`).
- [ ] Credenciales de MySQL con usuario dedicado (no `root`).
- [ ] Certificado TLS activo.
- [ ] Build de frontend generado (`public/build`).

## 2. Instalación en el servidor

```bash
git clone <repo> /var/www/facturacion
cd /var/www/facturacion

composer install --no-dev --optimize-autoloader --no-interaction
cp .env.production.example .env
# editar .env con credenciales reales
php artisan key:generate

npm ci
npm run build

php artisan migrate --force
php artisan storage:link

php artisan optimize
```

## 3. Permisos

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

El usuario del servidor web debe poder escribir en `storage/` y `bootstrap/cache/`.

## 4. Apache (document root en `public/`)

```apache
<VirtualHost *:443>
    ServerName facturacion.tu-dominio.com
    DocumentRoot /var/www/facturacion/public

    <Directory /var/www/facturacion/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/facturacion.tu-dominio.com/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/facturacion.tu-dominio.com/privkey.pem
</VirtualHost>
```

En Nginx, `root /var/www/facturacion/public;` y el bloque estándar de `try_files`:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

> Si se despliega en un subdirectorio de XAMPP, el `DocumentRoot` debe apuntar a
> `public/`, nunca a la raíz del proyecto (quedarían expuestos `.env`, `storage`, etc.).

## 5. Colas y tareas programadas

El sistema usa `QUEUE_CONNECTION=database`. Si se habilitan jobs:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Supervisor:

```ini
[program:facturacion-worker]
command=php /var/www/facturacion/artisan queue:work --sleep=3 --tries=3
process_name=%(program_name)s_%(process_num)02d
numprocs=2
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/facturacion/storage/logs/worker.log
stopwaitsecs=3600
```

Cron para el scheduler:

```cron
* * * * * cd /var/www/facturacion && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Seguridad

- `APP_DEBUG=false` (en `true` se filtran rutas, consultas SQL y variables).
- HTTPS obligatorio; `SESSION_SECURE_COOKIE=true` y `SESSION_ENCRYPT=true`.
- Registro público deshabilitado (`Auth::routes(['register' => false])`):
  los usuarios los crea un administrador.
- Solo los usuarios con `activo = 1` pueden iniciar sesión.
- Revisar periódicamente `storage/logs/`.
- Programar backups diarios de la base y de `storage/app/public`.

## 7. Actualizaciones

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

Si se modificó el esquema, hacer backup antes de migrar.

## 8. Verificación posterior (smoke test)

```bash
curl -I https://facturacion.tu-dominio.com/up        # 200
curl -I https://facturacion.tu-dominio.com/login     # 200
```

En la aplicación:

1. Iniciar sesión con un usuario administrador.
2. Crear una factura en USD y otra en BS.
3. Anular una factura y verificar que el stock vuelve.
4. Marcar una factura como pagada y comprobar la fecha de pago.
5. Crear un egreso de "Compra de mercancía" y verificar el aumento de stock.
6. Anular el egreso y verificar la reversión.
7. Descargar el CSV de tasas y comprobar el dashboard.

## 9. Rollback

```bash
php artisan down
git checkout <tag-anterior>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate:rollback --force   # solo si la migración es reversible
php artisan optimize
php artisan up
```

Las migraciones de reconciliación no eliminan columnas en su `down()`, por diseño.
