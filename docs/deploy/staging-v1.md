# Staging deployment + smoke test (v1)

Detta dokument beskriver den **faktiska deploy-ordningen** för A-Racing på staging.
Mål: inga dolda manuella steg.

## 1) Serverkrav

- PHP 8.2+
- MariaDB 10.6+
- Nginx
- PHP extensions: `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `curl`
- Skrivrättigheter för webbserver-användaren till:
  - `storage/cache`
  - `storage/logs`
  - `storage/imports`
  - `storage/sessions`
  - `public/uploads/product-images`

## 2) Webserver (Nginx)

Appen ska peka mot `public/` som document root.

```nginx
server {
    listen 8088;
    server_name _;

    root /var/www/aracing/current/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

## 3) Environment (.env)

Minimum för staging:

```dotenv
APP_NAME=A-Racing
APP_ENV=staging
APP_DEBUG=false
APP_URL=http://81.88.25.152:8088
APP_TIMEZONE=Europe/Stockholm
APP_LOCALE=sv

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aracing
DB_USERNAME=aracing_user
DB_PASSWORD=***

SESSION_SAVE_PATH=/var/www/aracing/shared/storage/sessions
```

> `SESSION_SAVE_PATH` är frivillig, men rekommenderas i staging/produktion för stabil session-hantering.

## 4) Deploy-ordning

1. Hämta ny kod (ny release-katalog eller git pull).
2. Installera dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Säkerställ kataloger + rättigheter:
   ```bash
   mkdir -p storage/cache storage/logs storage/imports storage/sessions public/uploads/product-images
   chown -R www-data:www-data storage public/uploads
   chmod -R 775 storage public/uploads
   ```
4. Kör preflight:
   ```bash
   php scripts/staging_doctor.php
   ```
5. Kör migrationer:
   ```bash
   php scripts/migrate.php
   ```
6. Verifiera migrationsstatus:
   ```bash
   php scripts/migrate.php --status
   ```
7. Smoke-testa routes:
   ```bash
   SMOKE_BASE_URL=http://81.88.25.152:8088 php scripts/staging_smoke_test.php
   ```

## 5) Vad som verifieras i smoke-test

- Storefront: startsida, kategori, produktsida, sök, cart, checkout
- Customer/account: login/register/account/wishlist/stock-alerts/compare
- Admin: dashboard, products, orders, purchasing, fitment, AI import, supplier monitoring, AI insights-vyer

Testet är ett **första blockeringsfilter**: routes ska svara utan 5xx/4xx blockerare.

## 6) Vanliga fel och lösning

### 403 på alla routes
- Kontrollera att vhost på port `8088` pekar på rätt `public/`.
- Kontrollera att request faktiskt når denna Nginx-site (inte annan reverse proxy/vhost-regel).

### Sessionproblem i admin (slumpmässiga utloggningar)
- Sätt `SESSION_SAVE_PATH` till en persistent, skrivbar katalog.
- Verifiera `php scripts/staging_doctor.php`.

### Migrationer kommer inte ikapp
- Kör `php scripts/migrate.php --status`.
- Verifiera att tabellen `schema_migrations` finns.
- Kontrollera SQL-fel i stdout/stderr och åtgärda rotorsaken i migrationsfil/DB-konfiguration.

Notering: `scripts/migrate.php` kör SQL-migrationer filvis utan att wrappa hela `.sql`-filer i en PDO-transaktion (MariaDB DDL kan göra implicit commit). En migration markeras i `schema_migrations` först efter att hela SQL-filen körts utan fel.

### Uppladdning/import fungerar inte
- Kontrollera skrivrättigheter på `storage/imports` och `public/uploads/product-images`.
