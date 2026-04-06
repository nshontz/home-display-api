# Production Deployment Checklist

This document outlines the steps to deploy the Home API and Recipe Management system to production.

## Pre-Deployment Checklist

### 1. Environment Configuration

Ensure your production `.env` file has these critical settings:

```env
# Application
APP_NAME="Home Display API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql  # or mysql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-secure-password

# Dashboard Integrations
ANYLIST_CODE=your-anylist-ical-code
SOLAR_EDGE_SITE_ID=your-site-id
SOLAR_EDGE_API_KEY=your-api-key
WEATHER_LOCATION=latitude,longitude

# AWS S3 (for recipe images/PDFs)
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com

# Recipe Storage (use S3 in production)
RECIPES_IMAGE_DISK=recipes
RECIPES_PDF_DISK=recipes

# Cache & Session (use Redis in production)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
```

### 2. Dependencies & Build

```bash
# Install PHP dependencies (production only)
composer install --no-dev --optimize-autoloader

# Install Node dependencies
npm ci

# Build frontend assets
npm run build

# Ensure node_modules is NOT deployed (use .gitignore)
```

### 3. Database Migration

```bash
# Run migrations
php artisan migrate --force

# Seed required data
php artisan db:seed --class=IngredientTagSeeder --force
php artisan db:seed --class=RecipeLayoutSeeder --force
php artisan db:seed --class=ClassifiedIngredientDataSeeder --force
```

### 4. Recipe System Setup

```bash
# Run the recipe setup script
./setup_recipes
```

This will:
- Create storage directories
- Set up storage symlink
- Seed ingredient tags, layouts, and ingredients
- Set permissions
- Create admin user prompt

### 5. Admin User Creation

```bash
# Create your first admin user
php artisan user:create-admin
```

### 6. Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache

# Generate application key (if not set)
php artisan key:generate
```

### 7. Storage & Permissions

```bash
# Ensure storage is writable
chmod -R 775 storage bootstrap/cache

# Set ownership (adjust user/group for your server)
chown -R www-data:www-data storage bootstrap/cache

# Create storage symlink if not exists
php artisan storage:link
```

### 8. S3 Setup (Production Storage)

Follow the guide in `documentation/s3-storage-setup.md`:

1. Create S3 bucket with unique name
2. Configure bucket policy for public read (images)
3. Set up IAM user with proper permissions
4. Update `.env` with S3 credentials
5. (Optional) Set up CloudFront CDN

### 9. Security

```bash
# Ensure debug mode is OFF
APP_DEBUG=false

# Use HTTPS
APP_URL=https://your-domain.com

# Secure session cookies
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Set proper CORS if using separate frontend
```

### 10. Web Server Configuration

**Nginx Example:**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/home-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Deployment Steps

### Initial Deployment

1. **Clone repository** to production server
2. **Copy `.env.example` to `.env`** and configure all variables
3. **Run composer install** (production dependencies only)
4. **Run npm install && npm run build**
5. **Run migrations**: `php artisan migrate --force`
6. **Run setup script**: `./setup_recipes`
7. **Create admin user**: `php artisan user:create-admin`
8. **Optimize application**: Run all cache commands
9. **Set permissions**: Ensure storage/bootstrap writable
10. **Test application**: Visit `/admin` and verify login

### Subsequent Deployments

```bash
# Pull latest code
git pull origin main

# Install/update dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers if using
php artisan queue:restart
```

## Post-Deployment Verification

### 1. Check Application Health

```bash
# Test database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB connected!';"

# Verify recipe system
php artisan tinker --execute="
echo 'Recipes: ' . App\Models\Recipe::count() . PHP_EOL;
echo 'Ingredients: ' . App\Models\Ingredient::count() . PHP_EOL;
echo 'Tags: ' . App\Models\IngredientTag::count() . PHP_EOL;
"
```

### 2. Test Key Features

- [ ] Admin panel accessible at `/admin`
- [ ] Can login with admin credentials
- [ ] Home dashboard displays (solar, weather, dinners)
- [ ] Can create/edit recipes in Filament
- [ ] Image uploads work (S3 or local)
- [ ] PDF generation works (download/preview)
- [ ] Recipe import works: `php artisan recipe:import "https://..."`

### 3. Monitor Logs

```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log

# Watch web server logs
tail -f /var/log/nginx/error.log
```

## Troubleshooting

### Storage Permission Issues

```bash
# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### S3 Upload Failures

1. Verify AWS credentials in `.env`
2. Check bucket name and region
3. Verify IAM permissions (s3:PutObject, s3:GetObject)
4. Test connection: `php artisan tinker` → `Storage::disk('recipes')->put('test.txt', 'test')`

### PDF Generation Issues

1. Clear view cache: `php artisan view:clear`
2. Check DomPDF config: `config/dompdf.php`
3. Verify storage writable: `storage/fonts`
4. Check error logs for specific issues

### Migration Issues

```bash
# Check migration status
php artisan migrate:status

# Rollback last batch if needed
php artisan migrate:rollback

# Fresh migration (CAUTION: destroys data)
php artisan migrate:fresh --seed --force
```

### Cache Issues

```bash
# Clear all caches
php artisan optimize:clear

# Or individually
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Maintenance Mode

```bash
# Enable maintenance mode
php artisan down --message="Updating application" --retry=60

# Disable maintenance mode
php artisan up
```

## Backup Strategy

### Database Backups

```bash
# PostgreSQL
pg_dump -U username -h host database_name > backup_$(date +%Y%m%d).sql

# MySQL
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### File Backups

```bash
# Backup storage directory
tar -czf storage_backup_$(date +%Y%m%d).tar.gz storage/

# Backup entire application
tar -czf app_backup_$(date +%Y%m%d).tar.gz \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='storage/logs' \
  .
```

### S3 Backups

Enable S3 versioning in bucket settings for automatic backup of uploaded files.

## Monitoring & Alerts

### Application Monitoring

Consider setting up:
- Laravel Telescope (dev)
- Laravel Horizon (queues)
- New Relic / Datadog
- Sentry for error tracking

### Server Monitoring

- Monitor disk space (recipes/images grow over time)
- Monitor database size
- Monitor memory usage (PDF generation can be intensive)
- Set up uptime monitoring

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secured
- [ ] AWS credentials secured (use IAM roles if on EC2)
- [ ] HTTPS enabled with valid SSL certificate
- [ ] Firewall configured (only 80/443 open)
- [ ] Database not publicly accessible
- [ ] Regular security updates: `composer update`
- [ ] `.env` file not committed to git
- [ ] Storage directory not publicly accessible
- [ ] Admin panel uses strong authentication

## Performance Optimization

### PHP-FPM Configuration

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### Opcache

```ini
; /etc/php/8.2/fpm/conf.d/opcache.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  ; Production only
```

### Database Indexing

Key indexes are already defined in migrations, but verify:
- `recipes.protein_id`
- `recipes.layout_id`
- `recipe_ingredients.recipe_id`
- `recipe_ingredients.ingredient_id`

## Support & Documentation

- Recipe system docs: `documentation/recipe-system-deployment.md`
- S3 setup guide: `documentation/s3-storage-setup.md`
- Ingredient parsing: `app/Services/IngredientService.php`
- PDF generation: `app/Services/RecipePDFService.php`

## Useful Commands Reference

```bash
# Data cleanup/maintenance
php artisan ingredient:fix-ranges          # Fix range parsing issues
php artisan ingredient:fix-invalid-units   # Fix invalid unit names
php artisan ingredient:fix-duplicates      # Fix duplicate words
php artisan recipe:assign-proteins         # Auto-assign recipe proteins

# Recipe management
php artisan recipe:import "url"            # Import single recipe
php artisan dinner:suggest-recipes         # Match dinners to recipes

# System status
php artisan tinker --execute="phpinfo(INFO_GENERAL);"  # PHP info
php artisan about                                       # Laravel info
```

---

**Last Updated:** April 5, 2026
**Laravel Version:** 10.x
**PHP Version:** 8.2+
