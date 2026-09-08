# PlanScraping

Sistema de prospección B2B con Laravel 12, Filament 3, SerpAPI y Brevo.

## Requisitos

- PHP 8.2+
- Composer
- SQLite (dev) o MySQL (producción)
- Cron + queue worker en producción

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Panel admin: `/admin`

Usuario seed:
- Email: `admin@jramirezr.com`
- Password: `password` (cambiar en producción)

## Cola y scheduler

```bash
# Terminal 1 — worker
php artisan queue:work

# Terminal 2 — scheduler (dev)
php artisan schedule:work
```

## Producción (AlmaLinux + CyberPanel)

### Cron (cada minuto)

```bash
* * * * * cd /home/USUARIO/DOMINIO/laravel && /usr/local/lsws/lsphp83/bin/php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor (queue worker)

```ini
[program:plan-scraping-worker]
command=/usr/local/lsws/lsphp83/bin/php /home/USUARIO/DOMINIO/laravel/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=USUARIO
```

### Brevo webhook

URL: `https://tudominio.com/webhooks/brevo`

Eventos: opened, click, hard_bounce, soft_bounce, complaint, unsubscribed

Header opcional: `X-Brevo-Token` = valor de `BREVO_WEBHOOK_SECRET`

## Variables de entorno

Ver `.env.example` para SerpAPI, Brevo SMTP y límites anti-spam.

## Comandos útiles

```bash
php artisan schedule:list
php artisan migrate:fresh --seed
```

## Seguridad

- No subir `.env` al repositorio
- Rotar claves API si fueron expuestas
- Verificar dominio en Brevo (SPF, DKIM, DMARC) antes de enviar en producción
