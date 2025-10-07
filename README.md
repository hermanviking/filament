## Filament Order Management

This project extends the default Filament admin panel with Visma.net ERP order synchronisation using the [Visma Sales Order API](https://salesorder.visma.net/swagger/index.html). You can:

- Create or edit orders in Filament and automatically calculate line discounts based on the Visma discount matrix.
- Push newly created or updated orders to Visma from the create/edit pages or the list table.
- Import existing Visma orders by number and keep them aligned via the **Refresh from Visma** action.

See [docs/visma-order-sync.md](docs/visma-order-sync.md) for detailed setup and troubleshooting guidance.

## Deploying to a server

The exact steps depend on your hosting environment, but a typical deployment for a Linux server running PHP 8.2, Nginx/Apache, and MySQL looks like this:

1. **Clone or update the code**
   ```bash
   cd /var/www/filament
   git pull origin work
   ```
2. **Install PHP dependencies**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```
3. **Install and build front-end assets** (if the Filament UI or theme changed)
   ```bash
   npm ci
   npm run build
   ```
<<<<<<< ours
4. **Set environment variables** by updating `.env` or the hosting control panel with the Visma keys described in [docs/visma-order-sync.md](docs/visma-order-sync.md). Ensure the `VISMA_SCOPE` value includes `vismanet_erp_service_api:create` and `vismanet_erp_service_api:update` so the integration can create and update orders, configure `VISMA_SALES_ORDER_TYPE` to the Visma document type segment used in the Sales Order API path (default `SO`), and configure the database and queue settings for production.
=======
4. **Set environment variables** by updating `.env` or the hosting control panel with the Visma keys described in [docs/visma-order-sync.md](docs/visma-order-sync.md). Ensure the `VISMA_SCOPE` value includes `vismanet_erp_service_api:create` and `vismanet_erp_service_api:update` so the integration can create and update orders, configure `VISMA_SALES_ORDER_TYPE` to the Visma document type segment used in the Sales Order API path (default `SO`), and provide the optional Sales Order API headers such as `VISMA_COMPANY_ID`, `VISMA_APPLICATION_TYPE`, `VISMA_USER_ID`, and `VISMA_SUBSCRIPTION_KEY` when your tenant requires them. Configure the database and queue settings for production.
>>>>>>> theirs
5. **Configure storage**
   ```bash
   php artisan storage:link
   mkdir -p storage/framework/{cache,sessions,views}
   mkdir -p storage/logs
   chown -R www-data:www-data storage bootstrap/cache
   ```
6. **Run database migrations**
   ```bash
   php artisan migrate --force
   ```
7. **Seed or import master data** (products/customers) as needed:
   ```bash
   php artisan visma:import-products
   php artisan visma:import-customers
   ```
8. **Cache configuration for performance**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
9. **Restart PHP workers** (FPM and queues) so they pick up the new code and environment variables.

Finally, ensure the `storage/discounts.json` file from Visma is deployed and readable so discount calculations work during order entry.

---

The remainder of this README contains the default Laravel documentation for reference.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>
