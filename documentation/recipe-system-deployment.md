# Recipe System - Deployment Guide

## Overview

This guide covers deploying the recipe management system to production. The recipe system adds comprehensive recipe storage, ingredient tracking, dietary classification, and PDF generation capabilities to the home-api application.

## Pre-Deployment Checklist

### 1. Environment Variables

Ensure these are set in production `.env`:

```env
# Recipe Import Settings
RECIPES_IMPORT_TIMEOUT=30
RECIPES_USER_AGENT="Home Recipe Manager/1.0 (https://yourdomain.com; recipe-import)"

# Storage
FILESYSTEM_DISK=public

# Optional: Cloud Storage (if using S3/DigitalOcean Spaces)
# AWS_BUCKET=your-bucket
# AWS_REGION=us-east-1
```

### 2. Required Assets

Place these files before deployment:

```
public/images/
├── logo.png                    # For PDF headers/footers (200x200px min, transparent PNG)
├── logo-dark.png              # For light backgrounds
├── favicon.ico                # Browser favicon
└── placeholder-recipe.png     # Default recipe image (800x600px)

storage/app/public/recipe_layouts/
├── classic-preview.png        # Layout preview images (1200x1600px)
├── modern-preview.png
└── rustic-preview.png
```

**Image Sources:**
- Logo: Your branding
- Placeholder: Use Unsplash, Pexels, or Pixabay (food-themed, neutral)
- Layout previews: Screenshots of example recipe PDFs

### 3. Database Backup

**CRITICAL:** Back up your production database before deployment:

```bash
# On production server
pg_dump -U your_db_user your_database > backup_$(date +%Y%m%d_%H%M%S).sql
```

## Deployment Process

### Step 1: Standard Application Update

Run the standard deployment script:

```bash
./update_application
```

This script handles:
- Maintenance mode
- Code pull from git
- Composer dependencies
- Database migrations (includes 10 new recipe tables)
- Frontend build
- Cache clearing
- Permissions

### Step 2: Recipe System Setup

Run the recipe-specific setup script:

```bash
./setup_recipes
```

This script handles:
- Storage directory creation
- Storage symlink verification
- Recipe data seeding (tags, layouts, ingredients)
- Admin user creation (if needed)
- Summary statistics

## Manual Deployment Steps

If you prefer to run commands manually instead of using `./setup_recipes`:

### 1. Create Storage Directories

```bash
mkdir -p storage/app/public/recipes/images
mkdir -p storage/app/public/recipes/pdfs
mkdir -p storage/app/public/recipe_layouts
mkdir -p public/images
```

### 2. Verify Storage Symlink

```bash
php artisan storage:link
```

### 3. Seed Recipe Data

```bash
# Seed ingredient tags (dietary labels)
php artisan db:seed --class=IngredientTagSeeder

# Seed recipe layouts (PDF templates)
php artisan db:seed --class=RecipeLayoutSeeder

# Seed pre-classified ingredients with dietary tags
php artisan db:seed --class=ClassifiedIngredientDataSeeder
```

### 4. Create Admin User (if needed)

```bash
php artisan user:create-admin
```

### 5. Verify Installation

```bash
# Check recipe counts
php artisan tinker --execute="
echo 'Ingredient Tags: ' . App\Models\IngredientTag::count() . PHP_EOL;
echo 'Recipe Layouts: ' . App\Models\RecipeLayout::count() . PHP_EOL;
echo 'Ingredients: ' . App\Models\Ingredient::count() . PHP_EOL;
echo 'Recipes: ' . App\Models\Recipe::count() . PHP_EOL;
"
```

## Post-Deployment Tasks

### 1. Access Admin Panel

Visit: `https://yourdomain.com/admin`

Login with the admin credentials you created.

### 2. Verify Recipe System

- Navigate to "Recipes" in the admin panel
- Click "Import from URL" to test recipe import
- Try importing a recipe from NYT Cooking or AllRecipes
- Verify ingredients are classified with dietary tags
- Check that recipe appears correctly

### 3. Test PDF Generation

(Once Phase 5 is implemented)
- Select a recipe in admin panel
- Generate PDF
- Verify layout, fonts, and formatting

### 4. Import Additional Recipes (Optional)

If you have a CSV file with recipes to import:

```bash
php artisan recipe:import-csv /path/to/recipes.csv --skip-existing
```

CSV format:
```csv
title,recipe_url
"Chicken Tikka Masala","https://cooking.nytimes.com/recipes/..."
"Classic Lasagna","https://www.seriouseats.com/recipes/..."
```

**Note:** Import includes 0.5s delay between requests to be respectful to source servers. Large imports will take time.

### 5. Set Storage Permissions

Ensure web server can write to storage:

```bash
chmod -R 775 storage/app/public/recipes
chown -R www-data:www-data storage/app/public/recipes
```

## Database Seeder Details

### IngredientTagSeeder
- **Creates:** 10 dietary/allergen tags
- **Tags:** Gluten-Free, Dairy, Vegan, Vegetarian, Nut Allergen, Shellfish, Soy, Egg, Fish, Organic
- **Includes:** Icons, colors, slugs
- **Safe to re-run:** Uses `firstOrCreate()`

### RecipeLayoutSeeder
- **Creates:** 3 PDF layout templates
- **Layouts:** Classic (Georgia/Palatino), Modern (Helvetica), Rustic (Century Schoolbook/Garamond)
- **Includes:** Font families, colors, spacing configurations
- **Safe to re-run:** Uses `firstOrCreate()`

### ClassifiedIngredientDataSeeder
- **Creates:** 934 pre-classified ingredients
- **Classifications:** 876 gluten-free, 706 vegan, 836 vegetarian, 87 dairy, 44 egg, etc.
- **Includes:** Ingredient names, plural forms, dietary tag assignments
- **Safe to re-run:** Uses `firstOrCreate()` and `syncWithoutDetaching()`
- **Source:** Auto-generated from development database classifications

## Rollback Procedure

If you need to rollback the recipe system:

### 1. Rollback Database

```bash
# Restore from backup
psql -U your_db_user your_database < backup_TIMESTAMP.sql

# Or rollback migrations individually
php artisan migrate:rollback --step=10
```

### 2. Rollback Code

```bash
git checkout main
git reset --hard HEAD~1  # If recipe system was in last commit
# OR
git revert <commit-hash>  # If you need to preserve history
```

### 3. Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
```

## Maintenance Commands

### Re-classify Ingredients

If you update classification rules:

```bash
# Clear existing tags
php artisan tinker --execute="DB::table('ingredient_ingredient_tag')->truncate();"

# Re-run classifier with new rules
php artisan db:seed --class=ProductionIngredientSeeder
```

### Import Single Recipe

```bash
php artisan recipe:import "https://cooking.nytimes.com/recipes/..."
```

### Export Ingredients List

```bash
php artisan tinker --execute="
\$ingredients = App\Models\Ingredient::with('tags')
    ->withCount('recipeIngredients')
    ->orderBy('recipe_ingredients_count', 'desc')
    ->get();

echo 'Ingredient,Usage Count,Tags' . PHP_EOL;
foreach(\$ingredients as \$i) {
    echo '\"' . \$i->name . '\",';
    echo \$i->recipe_ingredients_count . ',';
    echo '\"' . \$i->tags->pluck('name')->implode(', ') . '\"' . PHP_EOL;
}
" > ingredients_report.csv
```

### Check Ingredient Classification Coverage

```bash
php artisan tinker --execute="
\$total = App\Models\Ingredient::count();
\$untagged = App\Models\Ingredient::doesntHave('tags')->count();
\$tagged = \$total - \$untagged;
echo 'Total Ingredients: ' . \$total . PHP_EOL;
echo 'Tagged: ' . \$tagged . ' (' . round(\$tagged/\$total*100, 1) . '%)' . PHP_EOL;
echo 'Untagged: ' . \$untagged . ' (' . round(\$untagged/\$total*100, 1) . '%)' . PHP_EOL;
"
```

## Troubleshooting

### Issue: Storage symlink not working

**Symptom:** Images don't load, 404 errors for `/storage/recipes/...`

**Solution:**
```bash
# Remove old symlink
rm public/storage

# Recreate
php artisan storage:link

# Verify
ls -la public/ | grep storage
```

### Issue: PDF generation fails

**Symptom:** Error when generating PDF, missing fonts

**Solution:**
```bash
# Ensure fonts are available in layouts
php artisan tinker --execute="App\Models\RecipeLayout::all()->pluck('config');"

# Verify GD/Imagick is installed
php -m | grep -E '(gd|imagick)'
```

### Issue: Recipe import fails with 403/401

**Symptom:** Cannot import recipes from certain sites

**Solution:**
- Check `RECIPES_USER_AGENT` is set in `.env`
- Some sites may block scrapers - respect their robots.txt
- Try different source sites (NYT Cooking, Serious Eats work well)

### Issue: Ingredients not classified

**Symptom:** Ingredients created but no dietary tags assigned

**Solution:**
```bash
# Check tags exist
php artisan tinker --execute="echo App\Models\IngredientTag::count();"

# If 0, run tag seeder
php artisan db:seed --class=IngredientTagSeeder

# Re-run classification
php artisan db:seed --class=ClassifiedIngredientDataSeeder
```

### Issue: Permission denied on storage

**Symptom:** Cannot upload images, write PDFs

**Solution:**
```bash
# Fix ownership and permissions
sudo chown -R www-data:www-data storage/app/public/recipes
sudo chmod -R 775 storage/app/public/recipes
```

## Performance Considerations

### Database Indexes

The migrations include indexes on:
- `recipes.title`, `recipes.is_published`
- `ingredients.name`
- `recipe_ingredients.recipe_id`, `recipe_ingredients.ingredient_id`, `recipe_ingredients.order`
- All foreign keys

### Caching Strategy

Consider implementing cache for:
- Ingredient search/autocomplete
- Dietary tag counts
- Popular recipes

Example:
```php
// In RecipeResource or API controller
$popularIngredients = Cache::remember('ingredients:popular', 3600, function () {
    return Ingredient::withCount('recipeIngredients')
        ->orderBy('recipe_ingredients_count', 'desc')
        ->take(50)
        ->get();
});
```

### Image Storage

For production with many recipes:
- Consider cloud storage (AWS S3, DigitalOcean Spaces)
- Update `FILESYSTEM_DISK=s3` in `.env`
- Configure cloud credentials
- Images are stored at ~500KB average
- Estimate: 100 recipes = ~50MB

### Import Performance

- CSV imports include 0.5s delay between requests
- 159 recipes took ~2-3 minutes
- Run large imports during off-hours
- Consider background queue for imports

## Security Considerations

### 1. Admin Panel Access

- Use strong passwords for admin users
- Consider IP whitelisting for `/admin` routes
- Enable 2FA if using Filament plugins

### 2. Recipe Import

- User-Agent is set to identify your app
- Respects robots.txt by default
- 30-second timeout prevents hanging requests
- Downloaded images are validated

### 3. File Uploads

- Max image size: 5MB (configurable in `config/recipes.php`)
- Only images allowed in recipe uploads
- Files stored outside public web root (`storage/app/`)
- Accessed via symlink with Laravel's storage security

## Monitoring

### Metrics to Track

1. **Recipe System Usage**
   - Number of recipes imported
   - Number of PDFs generated
   - Most popular recipes (by view/export)

2. **Data Quality**
   - Percentage of ingredients classified
   - Number of recipes with images
   - Number of recipes with complete metadata

3. **Performance**
   - Average import time per recipe
   - PDF generation time
   - Storage usage growth

### Example Monitoring Queries

```bash
# Weekly recipe additions
php artisan tinker --execute="
echo 'Recipes added in last 7 days: ';
echo App\Models\Recipe::where('created_at', '>=', now()->subDays(7))->count();
"

# Storage usage
du -sh storage/app/public/recipes/
```

## Future Enhancements

See `/documentation/seeding-and-assets-guide.md` for:
- Recipe collections/cookbooks
- Ingredient substitutions
- Seasonal tags
- Equipment requirements
- Difficulty auto-assignment
- AI-powered classification improvements

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check web server logs: `/var/log/nginx/error.log` or `/var/log/apache2/error.log`
- Review this documentation
- Consult implementation plan: `/documentation/recipe-management-implementation-plan.md`

## Quick Reference

### One-Command Deployment

```bash
./update_application && ./setup_recipes
```

### Verify Everything

```bash
php artisan tinker --execute="
echo '=== Recipe System Status ===' . PHP_EOL;
echo 'Tags: ' . App\Models\IngredientTag::count() . PHP_EOL;
echo 'Layouts: ' . App\Models\RecipeLayout::count() . PHP_EOL;
echo 'Ingredients: ' . App\Models\Ingredient::count() . PHP_EOL;
echo 'Recipes: ' . App\Models\Recipe::count() . PHP_EOL;
echo 'Collections: ' . App\Models\RecipeCollection::count() . PHP_EOL;
echo PHP_EOL;
echo 'Storage writable: ' . (is_writable(storage_path('app/public/recipes')) ? 'Yes' : 'No') . PHP_EOL;
echo 'Storage linked: ' . (is_link(public_path('storage')) ? 'Yes' : 'No') . PHP_EOL;
"
```

### Admin Panel Access

```
URL: https://yourdomain.com/admin
Create admin: php artisan user:create-admin
```
