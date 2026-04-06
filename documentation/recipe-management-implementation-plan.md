# Recipe Management System - Implementation Plan

## Overview

This document outlines the implementation plan for a comprehensive recipe management system that allows importing, storing, editing, and sharing recipes with professional PDF output capabilities.

## Goals

1. Store recipes locally with full editing capabilities
2. Link recipes to existing dinner entries
3. Model ingredients separately from their usage (amounts, prep methods)
3. Tag ingredients with dietary attributes (gluten-free, dairy, vegan, etc.)
4. Import recipes from URLs automatically
5. Import recipes from existing dinner entries manually
6. Generate professional, branded PDF outputs with customizable layouts
7. Support recipe collections/cookbooks
8. Maintain attribution to original sources

## Database Structure

### New Tables

#### `recipes`
Core recipe information.

```php
- id (primary key)
- title (string, required)
- description (text, nullable)
- source_url (string, nullable) // Original recipe URL for attribution
- source_name (string, nullable) // e.g., "NYT Cooking", "Serious Eats"
- author (string, nullable)
- servings (integer, nullable)
- prep_time_minutes (integer, nullable)
- cook_time_minutes (integer, nullable)
- total_time_minutes (integer, nullable)
- difficulty (enum: 'easy', 'medium', 'hard', nullable)
- notes (text, nullable) // Personal notes/modifications
- image_path (string, nullable) // Single primary image
- layout_id (foreign key to recipe_layouts, nullable)
- protein_id (foreign key to proteins, nullable) // Link to existing protein
- is_published (boolean, default false) // Ready to share/print
- created_at, updated_at, deleted_at (soft deletes)
```

#### `recipe_steps`
Ordered cooking instructions.

```php
- id (primary key)
- recipe_id (foreign key to recipes)
- step_number (integer, required) // Order of execution
- instruction (text, required)
- time_minutes (integer, nullable) // Time for this specific step
- created_at, updated_at
```

#### `ingredients`
Master list of ingredients (normalized).

```php
- id (primary key)
- name (string, required, unique) // e.g., "onion", "chicken breast"
- plural_name (string, nullable) // e.g., "onions"
- category (string, nullable) // e.g., "produce", "dairy", "protein", "spice"
- created_at, updated_at
```

#### `ingredient_tags`
Dietary and attribute tags for ingredients.

```php
- id (primary key)
- name (string, required, unique) // e.g., "gluten-free", "dairy", "vegan", "vegetarian"
- slug (string, required, unique)
- type (enum: 'dietary_restriction', 'allergen', 'attribute')
- icon (string, nullable) // Icon class or emoji
- color (string, nullable) // Hex color for UI
- created_at, updated_at
```

#### `ingredient_ingredient_tag` (pivot)
Many-to-many relationship between ingredients and tags.

```php
- ingredient_id (foreign key to ingredients)
- ingredient_tag_id (foreign key to ingredient_tags)
- created_at
```

#### `recipe_ingredients` (pivot)
Links recipes to ingredients with usage details.

```php
- id (primary key)
- recipe_id (foreign key to recipes)
- ingredient_id (foreign key to ingredients)
- amount (string, nullable) // e.g., "1/2", "2", "1 1/2"
- unit (string, nullable) // e.g., "cup", "tablespoon", "medium", "large"
- preparation (string, nullable) // e.g., "diced", "roughly chopped", "minced"
- notes (string, nullable) // e.g., "or substitute with shallots"
- order (integer, default 0) // Display order in ingredient list
- is_optional (boolean, default false)
- created_at, updated_at
```

#### `recipe_layouts`
Predefined layout templates for PDF generation.

```php
- id (primary key)
- name (string, required) // e.g., "Classic", "Modern", "Rustic"
- slug (string, required, unique)
- description (text, nullable)
- config (json) // Layout configuration (fonts, colors, spacing, etc.)
- template_path (string) // Path to blade template
- is_active (boolean, default true)
- preview_image_path (string, nullable)
- created_at, updated_at
```

#### `recipe_collections`
Group recipes into cookbooks or collections.

```php
- id (primary key)
- title (string, required)
- description (text, nullable)
- cover_image_path (string, nullable)
- is_published (boolean, default false)
- created_at, updated_at
```

#### `collection_recipe` (pivot)
Many-to-many relationship between collections and recipes.

```php
- collection_id (foreign key to recipe_collections)
- recipe_id (foreign key to recipes)
- order (integer, default 0) // Order in collection
- created_at
```

### Modified Tables

#### `dinners`
Add foreign key to link dinner entries to recipes.

```php
// Add migration:
- recipe_id (foreign key to recipes, nullable)
```

## Model Relationships

### Recipe Model
```php
- belongsTo: Protein
- belongsTo: RecipeLayout
- belongsToMany: Ingredients (through recipe_ingredients pivot)
- hasMany: RecipeStep
- hasMany: Dinner (recipes can be used multiple times)
- belongsToMany: RecipeCollection (through collection_recipe pivot)
```

### Ingredient Model
```php
- belongsToMany: Recipe (through recipe_ingredients pivot)
- belongsToMany: IngredientTag (through ingredient_ingredient_tag pivot)
```

### IngredientTag Model
```php
- belongsToMany: Ingredient
```

### Dinner Model (existing, add)
```php
- belongsTo: Recipe (nullable)
```

### RecipeLayout Model
```php
- hasMany: Recipe
```

### RecipeCollection Model
```php
- belongsToMany: Recipe (through collection_recipe pivot)
```

## Service Architecture

### RecipeImportService
Handles importing recipes from external URLs.

**Location:** `app/Services/RecipeImportService.php`

**Methods:**
- `importFromUrl(string $url): array` - Scrapes and parses recipe from URL
- `detectRecipeFormat(string $html): ?string` - Identifies recipe schema (JSON-LD, Microdata, etc.)
- `parseJsonLdRecipe(array $jsonLd): array` - Parses schema.org Recipe JSON-LD
- `parseHeuristicRecipe(string $html): array` - Fallback scraping for non-structured recipes
- `extractIngredients(array $rawIngredients): array` - Parses ingredient strings into components
- `normalizeIngredientName(string $name): string` - Standardizes ingredient names

**Dependencies:**
- Guzzle (already in composer.json) for HTTP requests
- DOMDocument/DOMXPath for HTML parsing
- Consider: `symfony/dom-crawler` or `fabpot/goutte` for easier scraping

**HTTP Configuration:**
- Set custom User-Agent to properly identify the application
- User-Agent format: `"Home Recipe Manager/1.0 (https://yourdomain.com; recipe-import)"`
- Respects robots.txt and rate limiting
- Timeout: 30 seconds (configurable)

**Return Structure:**
```php
[
    'title' => 'Recipe Title',
    'description' => 'Recipe description',
    'source_url' => 'https://...',
    'source_name' => 'Website Name',
    'author' => 'Author Name',
    'servings' => 4,
    'prep_time' => 15,
    'cook_time' => 30,
    'total_time' => 45,
    'ingredients' => [
        [
            'name' => 'yellow onion',
            'amount' => '1',
            'unit' => 'medium',
            'preparation' => 'diced',
            'notes' => null,
        ],
        // ...
    ],
    'steps' => [
        'Step 1 instruction text',
        'Step 2 instruction text',
        // ...
    ],
    'image_url' => 'https://...',
]
```

### RecipeFactory
Creates recipe records from structured data.

**Location:** `app/Services/RecipeFactory.php`

**Methods:**
- `create(array $data): Recipe` - Main entry point
- `createRecipe(array $recipeData): Recipe` - Creates recipe record
- `attachIngredients(Recipe $recipe, array $ingredients): void` - Creates/links ingredients
- `createSteps(Recipe $recipe, array $steps): void` - Creates ordered steps
- `findOrCreateIngredient(string $name): Ingredient` - Normalizes and finds/creates ingredient
- `parseIngredientAmount(string $amount): array` - Parses fraction strings ("1/2" -> 0.5)
- `downloadAndStoreImage(Recipe $recipe, string $url): ?string` - Downloads recipe image

**Features:**
- Minimal ingredient normalization (preserves specificity like "yellow onion" vs "red onion")
- Fuzzy matching for finding existing ingredients
- Handles duplicate ingredients intelligently
- Transactional (rolls back on error)
- Returns fully loaded Recipe model with relationships

### RecipePDFService
Generates professional PDF outputs.

**Location:** `app/Services/RecipePDFService.php`

**Methods:**
- `generate(Recipe $recipe): string` - Generates PDF, returns path
- `generateCollection(RecipeCollection $collection): string` - Generates cookbook PDF
- `applyLayout(Recipe $recipe, RecipeLayout $layout): string` - Renders recipe with specific layout
- `renderBlade(Recipe $recipe, string $template): string` - Renders blade template to HTML
- `htmlToPdf(string $html): string` - Converts HTML to PDF

**Dependencies:**
- Add to composer.json: `barryvdh/laravel-dompdf` or `barryvdh/laravel-snappy` (wkhtmltopdf)
- Create blade templates in `resources/views/pdf/recipes/`

**Layout Configuration Example:**
```php
// In recipe_layouts.config JSON field
{
    "fonts": {
        "heading": "Georgia, serif",
        "body": "Helvetica, Arial, sans-serif",
        "accent": "Georgia, serif"
    },
    "colors": {
        "primary": "#2c3e50",
        "accent": "#e67e22",
        "text": "#34495e"
    },
    "spacing": {
        "page_margin": "20mm",
        "section_gap": "15px"
    },
    "image": {
        "position": "top|side|full",
        "max_height": "150mm"
    },
    "branding": {
        "show_logo": true,
        "logo_position": "header|footer"
    }
}
```

## Implementation Phases

### Phase 1: Database & Models (Foundation)
**Goal:** Set up all database tables and Eloquent models with relationships.

**Tasks:**
1. Create migration for `recipes` table
2. Create migration for `recipe_steps` table
3. Create migration for `ingredients` table
4. Create migration for `ingredient_tags` table
5. Create migration for `ingredient_ingredient_tag` pivot table
6. Create migration for `recipe_ingredients` pivot table
7. Create migration for `recipe_layouts` table
8. Create migration for `recipe_collections` table
9. Create migration for `collection_recipe` pivot table
10. Add `recipe_id` column to `dinners` table
11. Create all Eloquent models with proper relationships
12. Create model factories for testing
13. Create seeders for `ingredient_tags` (common dietary tags)
14. Create seeders for `recipe_layouts` (initial layout templates)

**Models to Create:**
- `app/Models/Recipe.php`
- `app/Models/RecipeStep.php`
- `app/Models/Ingredient.php`
- `app/Models/IngredientTag.php`
- `app/Models/RecipeLayout.php`
- `app/Models/RecipeCollection.php`

**Validation Rules:**
Define validation in models or create Form Requests for complex validation.

### Phase 2: Recipe Import Service
**Goal:** Build service to import recipes from URLs.

**Tasks:**
1. Install additional dependencies if needed (dom-crawler)
2. Create `RecipeImportService.php`
3. Configure HTTP client with custom User-Agent and timeout settings
4. Implement JSON-LD parser (schema.org Recipe format)
5. Implement heuristic parser for non-structured recipes
6. Create ingredient string parser (amount, unit, prep extraction)
7. Add ingredient name normalization logic (minimal, preserves specificity)
8. Create tests for import service with fixtures from NYT, Serious Eats, AllRecipes
9. Add error handling, logging, and rate limiting

**Testing:**
- Unit tests with mock HTML responses
- Test against real recipe sites (NYT, Serious Eats, AllRecipes, etc.)
- Test ingredient parsing edge cases

### Phase 3: Recipe Factory Service
**Goal:** Build reusable factory for creating recipes from structured data.

**Tasks:**
1. Create `RecipeFactory.php`
2. Implement transactional recipe creation
3. Implement ingredient matching/creation logic
4. Implement ingredient tagging (auto-detect common tags)
5. Implement step creation with ordering
6. Implement image download and storage
7. Create tests for factory
8. Add validation and error handling

**Ingredient Normalization:**
- **Preserve specificity** - Keep ingredient details like "yellow onion", "red onion", "sweet onion" distinct
- Plural to singular only when unambiguous ("onions" -> "onion", but keep "yellow onions" -> "yellow onion")
- Consistent capitalization (lowercase for comparison, preserve original for display)
- Trim whitespace and normalize unicode characters
- Handle common misspellings (e.g., "tumeric" -> "turmeric")
- **Do not** over-generalize - accuracy over convenience

### Phase 4: Filament Resources (UI)
**Goal:** Create Filament admin interface for recipe management.

**Tasks:**
1. Create `RecipeResource.php` with full CRUD
2. Create `IngredientResource.php`
3. Create `IngredientTagResource.php`
4. Create `RecipeLayoutResource.php`
5. Create `RecipeCollectionResource.php`
6. Add custom Filament action: "Import from URL"
7. Add custom Filament action: "Import from Dinner"
8. Add custom Filament action: "Generate PDF"
9. Create custom relationship manager for recipe ingredients
10. Create custom relationship manager for recipe steps
11. Add image upload field for recipes
12. Add layout preview in RecipeLayoutResource

**Filament Features:**
- Rich text editor for recipe notes
- Repeater for recipe steps (drag to reorder)
- Select/create for ingredients with tags displayed
- Visual layout selector for recipe layouts
- Bulk actions for generating PDFs
- Recipe preview modal

**Custom Actions:**

```php
// In RecipeResource
Action::make('importFromUrl')
    ->form([
        TextInput::make('url')->url()->required(),
    ])
    ->action(function (array $data) {
        $importService = new RecipeImportService();
        $recipeData = $importService->importFromUrl($data['url']);
        $recipe = RecipeFactory::create($recipeData);
        return redirect()->route('filament.resources.recipes.edit', $recipe);
    });

Action::make('importFromDinner')
    ->form([
        Select::make('dinner_id')
            ->options(Dinner::whereNotNull('event->location')->pluck('title', 'id'))
            ->required(),
    ])
    ->action(function (array $data) {
        $dinner = Dinner::find($data['dinner_id']);
        $url = json_decode($dinner->event)->location;
        // Import logic...
    });

Action::make('generatePdf')
    ->action(function (Recipe $record) {
        $pdfService = new RecipePDFService();
        $path = $pdfService->generate($record);
        return response()->download(storage_path('app/' . $path));
    });
```

### Phase 5: PDF Generation
**Goal:** Implement professional PDF output with multiple layouts.

**Tasks:**
1. Install PDF generation package (`barryvdh/laravel-dompdf`)
2. Create base blade template: `resources/views/pdf/recipes/base.blade.php`
3. Create layout templates:
   - `resources/views/pdf/recipes/layouts/classic.blade.php`
   - `resources/views/pdf/recipes/layouts/modern.blade.php`
   - `resources/views/pdf/recipes/layouts/rustic.blade.php`
4. Create `RecipePDFService.php`
5. Implement single recipe PDF generation
6. Implement collection/cookbook PDF generation
7. Add CSS for print optimization
8. Add branding elements (logo, footer, etc.)
9. Create PDF preview route for testing
10. Add "Download PDF" button to Filament resource

**Layout Features:**
- Configurable fonts, colors, spacing
- Image positioning options
- Page break logic for long recipes
- Ingredient list styling (checkboxes for shopping)
- Step numbering with clear separation
- Attribution footer with source URL
- Dietary tag badges
- Recipe metadata display (time, servings, difficulty)

**Template Structure:**
```blade
{{-- resources/views/pdf/recipes/layouts/classic.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <style>
        /* CSS based on layout config */
        @page { margin: {{ $layout->config['spacing']['page_margin'] }}; }
        body { font-family: {{ $layout->config['fonts']['body'] }}; }
        h1 { font-family: {{ $layout->config['fonts']['heading'] }}; }
    </style>
</head>
<body>
    <header>
        @if($layout->config['branding']['show_logo'])
            <img src="{{ public_path('images/logo.png') }}" alt="Logo">
        @endif
        <h1>{{ $recipe->title }}</h1>
    </header>

    @if($recipe->image_path)
        <img src="{{ storage_path('app/' . $recipe->image_path) }}" class="recipe-image">
    @endif

    <section class="metadata">
        <div>Servings: {{ $recipe->servings }}</div>
        <div>Total Time: {{ $recipe->total_time_minutes }}min</div>
        <div>Difficulty: {{ $recipe->difficulty }}</div>
    </section>

    <section class="tags">
        @foreach($recipe->dietaryTags() as $tag)
            <span class="tag">{{ $tag->name }}</span>
        @endforeach
    </section>

    <section class="ingredients">
        <h2>Ingredients</h2>
        <ul>
            @foreach($recipe->recipeIngredients as $ri)
                <li>
                    {{ $ri->amount }} {{ $ri->unit }} {{ $ri->ingredient->name }}
                    @if($ri->preparation), {{ $ri->preparation }}@endif
                </li>
            @endforeach
        </ul>
    </section>

    <section class="steps">
        <h2>Instructions</h2>
        <ol>
            @foreach($recipe->steps()->orderBy('step_number')->get() as $step)
                <li>{{ $step->instruction }}</li>
            @endforeach
        </ol>
    </section>

    @if($recipe->notes)
        <section class="notes">
            <h2>Notes</h2>
            <p>{{ $recipe->notes }}</p>
        </section>
    @endif

    <footer>
        @if($recipe->source_url)
            <p>Adapted from: {{ $recipe->source_name }} - {{ $recipe->source_url }}</p>
        @endif
        <p>© {{ date('Y') }} Your Name</p>
    </footer>
</body>
</html>
```

### Phase 6: Recipe-Dinner Integration
**Goal:** Connect existing dinner entries to recipes.

**Tasks:**
1. Add recipe selector to `DinnerResource.php` in Filament
2. Create artisan command: `php artisan dinner:suggest-recipes` (match dinners to recipes by title)
3. Add "Import Recipe" action to dinner entries with existing URLs
4. Update `Controller@home()` to include recipe data when returning dinner info
5. Display recipe link in frontend when dinner has associated recipe
6. Add recipe count to dinner statistics

### Phase 7: Advanced Features (Future)
**Tasks:**
1. Recipe search and filtering by tags
2. Shopping list generation from recipe ingredients
3. Recipe scaling (adjust serving sizes)
4. Ingredient substitution suggestions
5. Nutritional information (integrate with nutrition API)
6. Recipe ratings and favorites
7. Recipe sharing (public URLs)
8. Print-friendly single-page view
9. Mobile-optimized view
10. Recipe duplication/forking
11. Version history for recipe edits
12. Batch PDF generation for entire cookbook
13. Recipe cost estimation

## API Endpoints (Optional Future Enhancement)

If you want to expose recipes via API:

```php
// routes/api.php
Route::prefix('recipes')->group(function () {
    Route::get('/', [RecipeController::class, 'index']);
    Route::get('/{recipe}', [RecipeController::class, 'show']);
    Route::post('/import', [RecipeController::class, 'import']);
    Route::get('/{recipe}/pdf', [RecipeController::class, 'downloadPdf']);
    Route::get('/tags', [RecipeController::class, 'tags']);
});
```

## Testing Strategy

### Unit Tests
- RecipeImportService: Test parsing different recipe formats
- RecipeFactory: Test recipe creation with various data structures
- Ingredient parsing: Test amount/unit/prep extraction
- Ingredient normalization: Test name standardization

### Feature Tests
- Recipe CRUD operations
- Recipe import from URL
- Recipe-Dinner relationship
- PDF generation
- Tag filtering

### Browser Tests (Optional)
- Filament resource interactions
- Recipe import workflow
- PDF preview and download

## Configuration Files

### config/recipes.php (New)
```php
<?php

return [
    // Supported recipe schema formats
    'supported_schemas' => [
        'json-ld',
        'microdata',
        'rdfa',
    ],

    // Image storage
    'image_disk' => 'public',
    'image_path' => 'recipes/images',
    'max_image_size' => 5120, // KB

    // PDF generation
    'pdf_disk' => 'local',
    'pdf_path' => 'recipes/pdfs',
    'default_layout' => 'classic',

    // Import
    'import_timeout' => 30, // seconds
    'download_images' => true,
    'user_agent' => 'Home Recipe Manager/1.0 (https://yourdomain.com; recipe-import)',
    'respect_robots_txt' => true,

    // Ingredient normalization (minimal - preserves specificity)
    'normalize_ingredients' => true,
    'preserve_ingredient_specificity' => true, // Keeps "yellow onion" vs "red onion" distinct
    'ingredient_misspellings' => [
        'tumeric' => 'turmeric',
        'cinamon' => 'cinnamon',
        'parsely' => 'parsley',
        // Add more common misspellings
    ],
];
```

## Data Seeding

### Initial Ingredient Tags
```php
// database/seeders/IngredientTagSeeder.php
$tags = [
    ['name' => 'Gluten-Free', 'slug' => 'gluten-free', 'type' => 'dietary_restriction', 'color' => '#f39c12'],
    ['name' => 'Dairy', 'slug' => 'dairy', 'type' => 'allergen', 'color' => '#3498db'],
    ['name' => 'Vegan', 'slug' => 'vegan', 'type' => 'dietary_restriction', 'color' => '#27ae60'],
    ['name' => 'Vegetarian', 'slug' => 'vegetarian', 'type' => 'dietary_restriction', 'color' => '#2ecc71'],
    ['name' => 'Nut Allergen', 'slug' => 'nut-allergen', 'type' => 'allergen', 'color' => '#e74c3c'],
    ['name' => 'Shellfish', 'slug' => 'shellfish', 'type' => 'allergen', 'color' => '#9b59b6'],
    ['name' => 'Soy', 'slug' => 'soy', 'type' => 'allergen', 'color' => '#e67e22'],
    ['name' => 'Organic', 'slug' => 'organic', 'type' => 'attribute', 'color' => '#16a085'],
];
```

### Initial Recipe Layouts
```php
// database/seeders/RecipeLayoutSeeder.php
$layouts = [
    [
        'name' => 'Classic',
        'slug' => 'classic',
        'description' => 'Traditional recipe layout with elegant serif typography',
        'template_path' => 'pdf.recipes.layouts.classic',
        'config' => [
            'fonts' => [
                'heading' => 'Georgia, serif',
                'body' => 'Palatino, "Palatino Linotype", serif',
                'accent' => 'Georgia, serif'
            ],
            'colors' => ['primary' => '#2c3e50', 'accent' => '#8b4513', 'text' => '#1a1a1a'],
            'spacing' => ['page_margin' => '20mm', 'section_gap' => '8mm'],
            'image' => ['position' => 'top', 'max_height' => '120mm'],
        ],
    ],
    [
        'name' => 'Modern',
        'slug' => 'modern',
        'description' => 'Clean, contemporary layout with sans-serif fonts',
        'template_path' => 'pdf.recipes.layouts.modern',
        'config' => [
            'fonts' => [
                'heading' => 'Helvetica, Arial, sans-serif',
                'body' => 'Helvetica, Arial, sans-serif',
                'accent' => '"Helvetica Neue", Helvetica, sans-serif'
            ],
            'colors' => ['primary' => '#1a1a1a', 'accent' => '#e74c3c', 'text' => '#333333'],
            'spacing' => ['page_margin' => '15mm', 'section_gap' => '6mm'],
            'image' => ['position' => 'side', 'max_height' => '100mm'],
        ],
    ],
    [
        'name' => 'Rustic',
        'slug' => 'rustic',
        'description' => 'Warm, farmhouse-style layout with mixed typography',
        'template_path' => 'pdf.recipes.layouts.rustic',
        'config' => [
            'fonts' => [
                'heading' => '"Century Schoolbook", Georgia, serif',
                'body' => 'Garamond, "Times New Roman", serif',
                'accent' => 'Georgia, serif'
            ],
            'colors' => ['primary' => '#3a2920', 'accent' => '#d4a574', 'text' => '#2c2416'],
            'spacing' => ['page_margin' => '25mm', 'section_gap' => '10mm'],
            'image' => ['position' => 'top', 'max_height' => '140mm'],
        ],
    ],
];
```

**Font Rationale:**
- All fonts are print-safe system fonts available in PDF renderers
- Serif fonts (Georgia, Palatino, Century Schoolbook, Garamond) provide classic readability
- Sans-serif options (Helvetica, Arial) for modern, clean aesthetics
- Fallback fonts ensure consistent rendering across systems
- Font combinations tested for print clarity and aesthetic appeal

## File Structure Summary

```
app/
├── Models/
│   ├── Recipe.php
│   ├── RecipeStep.php
│   ├── Ingredient.php
│   ├── IngredientTag.php
│   ├── RecipeLayout.php
│   └── RecipeCollection.php
├── Services/
│   ├── RecipeImportService.php
│   ├── RecipeFactory.php
│   └── RecipePDFService.php
├── Filament/Resources/
│   ├── RecipeResource.php
│   ├── IngredientResource.php
│   ├── IngredientTagResource.php
│   ├── RecipeLayoutResource.php
│   └── RecipeCollectionResource.php
├── Http/Controllers/
│   └── RecipeController.php (optional, for API)

database/
├── migrations/
│   ├── xxxx_create_recipes_table.php
│   ├── xxxx_create_recipe_steps_table.php
│   ├── xxxx_create_ingredients_table.php
│   ├── xxxx_create_ingredient_tags_table.php
│   ├── xxxx_create_ingredient_ingredient_tag_table.php
│   ├── xxxx_create_recipe_ingredients_table.php
│   ├── xxxx_create_recipe_layouts_table.php
│   ├── xxxx_create_recipe_collections_table.php
│   ├── xxxx_create_collection_recipe_table.php
│   └── xxxx_add_recipe_id_to_dinners_table.php
└── seeders/
    ├── IngredientTagSeeder.php
    └── RecipeLayoutSeeder.php

resources/
└── views/
    └── pdf/
        └── recipes/
            ├── base.blade.php
            └── layouts/
                ├── classic.blade.php
                ├── modern.blade.php
                └── rustic.blade.php

config/
└── recipes.php

tests/
├── Unit/
│   ├── RecipeImportServiceTest.php
│   ├── RecipeFactoryTest.php
│   └── IngredientParserTest.php
└── Feature/
    ├── RecipeManagementTest.php
    └── RecipePDFGenerationTest.php
```

## Risks & Considerations

### Technical Challenges
1. **Web Scraping Reliability**: Recipe websites may change structure or block scraping
   - Mitigation: Support multiple parsing strategies, graceful degradation

2. **Ingredient Parsing Accuracy**: Natural language is complex
   - Mitigation: Machine learning approach or extensive pattern library

3. **PDF Generation Performance**: Large cookbooks may be slow
   - Mitigation: Queue-based generation, caching, progress indicators

4. **Image Storage**: Recipe images can be large
   - Mitigation: Image optimization, CDN, cleanup jobs

### Data Quality
1. **Ingredient Specificity vs. Consistency**: Balance between preserving recipe accuracy ("yellow onion") and avoiding duplicate similar ingredients
   - Mitigation: Fuzzy matching for suggestions, admin review interface, merge functionality for true duplicates

2. **Duplicate Recipes**: Same recipe from different sources
   - Mitigation: Duplicate detection, merge functionality

3. **Tag Accuracy**: Automatic tag assignment may be wrong
   - Mitigation: Manual override, confidence scores

### Legal
1. **Copyright**: Recipes may be copyrighted
   - Mitigation: Clear attribution, link to original, fair use for personal use

2. **Image Rights**: Downloaded images may have restrictions
   - Mitigation: Provide upload option, user acknowledges rights

## Success Metrics

1. Successfully import 90%+ of recipes from major recipe sites
2. Accurate ingredient parsing (95%+ correct)
3. Generate professional PDFs in under 5 seconds per recipe
4. Zero data loss during recipe creation/editing
5. Clear relationship tracking between dinners and recipes

## Next Steps

1. **Review This Plan**: Discuss any modifications or priorities
2. **Set Up Environment**: Install PDF generation package
3. **Begin Phase 1**: Start with database migrations and models
4. **Iterative Development**: Build and test each phase before moving to next
5. **User Testing**: Test with real recipes from your dinners table

## Questions to Resolve

1. Should recipes be soft-deletable or hard-delete?
   - **Recommendation**: Soft delete to preserve dinner relationships

2. Should we support multiple images per recipe?
   - **Current Plan**: Single image, can expand later

3. How to handle recipe variations (e.g., different prep methods)?
   - **Recommendation**: Use recipe notes field, or add "parent_recipe_id" for variations

4. Public sharing vs private only?
   - **Current Plan**: Start private, add public sharing in Phase 7

5. Should ingredient amounts support ranges (e.g., "1-2 cups")?
   - **Recommendation**: Yes, store as string but add min/max fields for future

6. Automatic tag assignment or manual only?
   - **Recommendation**: Start manual, add auto-suggestion in Phase 7
