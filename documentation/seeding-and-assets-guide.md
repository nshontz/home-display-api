# Recipe System - Seeding & Assets Guide

## Current Status Summary

✅ **Completed:**
- 159 recipes imported from dinners CSV
- 934 ingredients created and classified
- 876 gluten-free ingredients
- 706 vegan ingredients
- 836 vegetarian ingredients
- 87 dairy ingredients
- 44 egg ingredients
- 17 soy ingredients
- 9 shellfish ingredients
- 8 fish ingredients
- 3 nut allergen ingredients

## Available Seeders

### 1. IngredientTagSeeder ✅
**Status:** Complete and run
**Location:** `database/seeders/IngredientTagSeeder.php`
**Seeds:**
- Gluten-Free
- Dairy
- Vegan
- Vegetarian
- Nut Allergen
- Shellfish
- Soy
- Egg
- Fish
- Organic

### 2. RecipeLayoutSeeder ✅
**Status:** Complete and run
**Location:** `database/seeders/RecipeLayoutSeeder.php`
**Seeds:**
- Classic Layout (Georgia/Palatino fonts)
- Modern Layout (Helvetica/Arial fonts)
- Rustic Layout (Century Schoolbook/Garamond fonts)

### 3. ProductionIngredientSeeder ✅
**Status:** Complete and run
**Location:** `database/seeders/ProductionIngredientSeeder.php`
**Purpose:** Automatically classifies all ingredients with appropriate dietary tags
**Run command:** `php artisan db:seed --class=ProductionIngredientSeeder`

## Recommended Additional Seeders

### 4. SampleRecipeCollectionSeeder (TODO)
**Purpose:** Create example cookbooks/collections
**Suggested Collections:**
- "Weeknight Favorites" - Quick 30-minute meals
- "Vegetarian Delights" - Best vegetarian recipes
- "Comfort Food Classics" - Hearty comfort meals
- "Healthy & Light" - Low-calorie options
- "Holiday Entertaining" - Special occasion recipes

**Implementation:**
```php
// Create collection
$collection = RecipeCollection::create([
    'title' => 'Weeknight Favorites',
    'description' => 'Quick and easy meals for busy weeknights',
    'is_published' => true,
]);

// Attach recipes
$recipes = Recipe::where('total_time_minutes', '<=', 30)
    ->orderBy('title')
    ->take(20)
    ->get();

foreach ($recipes as $index => $recipe) {
    $collection->recipes()->attach($recipe->id, ['order' => $index + 1]);
}
```

### 5. DefaultUserSeeder (TODO)
**Purpose:** Create admin user for production
**Run command:** Already exists: `php artisan user:create-admin`

## Assets Needed

### 1. Logo/Branding
**Purpose:** PDF generation, admin panel
**Files needed:**
- `public/images/logo.png` (for PDF footers/headers)
- `public/images/logo-dark.png` (for light backgrounds)
- `public/images/favicon.ico`
- Recommended size: 200x200px minimum, transparent PNG

### 2. Placeholder Recipe Image
**Purpose:** Default image for recipes without photos
**File:** `public/images/placeholder-recipe.png`
**Recommended:** 800x600px, food-themed illustration or pattern
**Alternatives:**
- Use Unsplash API for random food images
- Create a simple fork/knife icon design

### 3. Layout Preview Images
**Purpose:** Show layout options in Filament admin
**Files needed:**
```
storage/app/public/recipe_layouts/
├── classic-preview.png
├── modern-preview.png
└── rustic-preview.png
```
**Content:** Screenshot or mockup of each PDF layout style

### 4. Collection Cover Images (Optional)
**Purpose:** Cookbook cover images
**Files:** User-uploaded or curated stock photos
**Suggested categories:**
- Weeknight dinners
- Holiday entertaining
- Healthy eating
- Comfort food
- Vegetarian meals

## Production Deployment Checklist

### Database Seeding Order
Run these seeders in production:

```bash
# 1. Ingredient tags (dietary labels)
php artisan db:seed --class=IngredientTagSeeder

# 2. Recipe layouts (PDF templates)
php artisan db:seed --class=RecipeLayoutSeeder

# 3. Import recipes from your CSV
php artisan recipe:import-csv documentation/dinners.csv --skip-existing

# 4. Classify ingredients with tags
php artisan db:seed --class=ProductionIngredientSeeder

# 5. (Optional) Create sample collections
# php artisan db:seed --class=SampleRecipeCollectionSeeder

# 6. Create admin user
php artisan user:create-admin
```

### Storage Setup
```bash
# Create storage symlink (if not exists)
php artisan storage:link

# Create necessary directories
mkdir -p storage/app/public/recipes/images
mkdir -p storage/app/public/recipes/pdfs
mkdir -p storage/app/public/recipe_layouts
mkdir -p public/images
```

### Environment Variables
Ensure these are set in production `.env`:
```env
# Recipe import settings
RECIPES_IMPORT_TIMEOUT=30
RECIPES_USER_AGENT="Home Recipe Manager/1.0 (https://yourdomain.com; recipe-import)"

# Storage
FILESYSTEM_DISK=public

# Optional: If using cloud storage
# AWS_BUCKET=your-bucket
# AWS_REGION=us-east-1
```

## Maintenance Commands

### Re-classify Ingredients
If you add new classification rules or fix bugs:
```bash
# Clear existing tags
php artisan tinker --execute="DB::table('ingredient_ingredient_tag')->truncate();"

# Re-run classifier
php artisan db:seed --class=ProductionIngredientSeeder
```

### Import New Recipes
```bash
# Single recipe
php artisan recipe:import "https://cooking.nytimes.com/recipes/..."

# Batch from CSV
php artisan recipe:import-csv path/to/new-recipes.csv
```

### Export Ingredient List for Manual Review
```bash
php artisan tinker --execute="
\$ingredients = App\Models\Ingredient::with('tags')
    ->withCount('recipeIngredients')
    ->orderBy('recipe_ingredients_count', 'desc')
    ->get();

echo 'Ingredient,Usage Count,Tags' . PHP_EOL;
foreach(\$ingredients as \$i) {
    echo '\\"' . \$i->name . '\\",';
    echo \$i->recipe_ingredients_count . ',';
    echo '\\"' . \$i->tags->pluck('name')->implode(', ') . '\\"' . PHP_EOL;
}
" > documentation/ingredients_classified.csv
```

## Future Enhancements

### Additional Data to Seed

1. **Common Ingredient Substitutions**
   - Create `ingredient_substitutions` table
   - Seed with common swaps (buttermilk → milk + vinegar)

2. **Recipe Categories/Cuisines**
   - Italian, Mexican, Asian, American, etc.
   - Breakfast, Lunch, Dinner, Dessert, Snacks

3. **Equipment/Tools**
   - Slow cooker, Instant Pot, Air Fryer, Grill
   - Required equipment per recipe

4. **Difficulty Levels**
   - Already exists in schema
   - Could auto-assign based on step count and techniques

5. **Seasonal Tags**
   - Spring, Summer, Fall, Winter
   - Holiday-specific (Thanksgiving, Christmas, etc.)

### Automated Classification Improvements

The `ProductionIngredientSeeder` could be enhanced with:
- Machine learning classification
- API integration with nutrition databases
- Crowdsourced corrections from users
- Ingredient synonym detection

### Image Assets

Consider using:
- **Unsplash API** for free high-quality food images
- **Pixabay** for public domain food photography
- **AI Image Generation** (DALL-E, Midjourney) for custom illustrations
- User uploads from recipe sources (with proper attribution)

## Quick Start for New Environments

```bash
# Clone and setup
composer install
npm install
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed --class=IngredientTagSeeder
php artisan db:seed --class=RecipeLayoutSeeder

# Storage
php artisan storage:link

# Assets (add your logo, placeholders, etc.)
# Place files in public/images/

# Import data
php artisan recipe:import-csv documentation/dinners.csv
php artisan db:seed --class=ProductionIngredientSeeder

# Create admin
php artisan user:create-admin

# Access admin panel
# Visit: http://your-domain/admin
```

## Asset Preparation Tips

### Logo Creation
1. Keep it simple and recognizable
2. Works well at small sizes (favicon)
3. Consider both light and dark backgrounds
4. Export in multiple formats: PNG (transparent), SVG (scalable), ICO (favicon)

### Recipe Placeholder
1. Use a neutral, appealing food image or pattern
2. Avoid specific dishes (should work for any recipe type)
3. Consider: cutting board with ingredients, kitchen utensils, abstract food pattern
4. Free sources: Unsplash, Pexels, Pixabay

### Layout Previews
1. Use actual recipe content for authenticity
2. Show distinctive features of each layout
3. Keep dimensions consistent (e.g., 1200x1600px)
4. Include visible differences: fonts, colors, spacing

## Performance Considerations

### Large Imports
- The CSV import adds 0.5s delay between requests (respectful to servers)
- 159 recipes took approximately 2-3 minutes
- Consider running imports during off-hours

### Tag Classification
- 934 ingredients classified in ~2 seconds
- Runs efficiently even with thousands of ingredients
- Safe to re-run (checks for existing tags)

### Storage Requirements
- Recipes with images: ~500KB average per recipe
- PDFs: ~1-2MB per recipe
- Estimate: 100 recipes = ~150MB storage
- Consider cloud storage (S3, DigitalOcean Spaces) for production
