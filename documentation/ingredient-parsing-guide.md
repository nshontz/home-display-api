# Ingredient Parsing System

## Overview

The ingredient parsing system has been refactored into a dedicated `IngredientService` that handles complex parsing logic for recipe ingredients. This service is designed to be robust, tunable, and maintainable.

## Architecture

### IngredientService (`app/Services/IngredientService.php`)

Central service for all ingredient-related parsing and normalization:

- **Parsing**: Extracts structured data from raw ingredient strings
- **Normalization**: Cleans and standardizes ingredient names
- **Matching**: Finds existing ingredients with fuzzy matching
- **Classification**: Prepares ingredient names for dietary tag matching

### Integration Points

1. **RecipeImportService** - Uses IngredientService to parse ingredients during recipe import
2. **RecipeFactory** - Uses IngredientService to find or create ingredients
3. **ProductionIngredientSeeder** - Uses IngredientService for classification normalization

## Parsing Capabilities

### 1. Range Handling

Converts ingredient ranges to the upper bound for consistency:

```
Input:  "3 to 4 lime leaves"
Output: amount: "4", name: "lime leaves"

Input:  "1-2 tablespoons olive oil"
Output: amount: "2", unit: "tablespoons", name: "olive oil"
```

### 2. Unit Detection

Recognizes a comprehensive list of units (volume, weight, count):

**Volume:**
- Full words: cup, tablespoon, teaspoon, pint, quart, gallon, liter, milliliter
- Abbreviations: c, tbsp, tbs, T, tsp, ts, t, pt, qt, gal, l, L, ml, mL
- Fluid: fluid ounce, fl oz, fl. oz.

**Weight:**
- Full words: pound, ounce, gram, kilogram
- Abbreviations: lb, lbs, lb., oz, oz., g, kg

**Count/Containers:**
- piece, package, can, jar, box, bag, bunch, sprig, head, clove, stalk, slice, strip, block

**Approximations:**
- pinch, dash, handful

### 3. Compound Unit Simplification

Simplifies compound units to the primary unit:

```
Input:  "1 cup/8 ounces whole-milk ricotta"
Output: amount: "1", unit: "cup", name: "whole-milk ricotta"
```

### 4. Leading Adjective Removal

Removes non-descriptive leading adjectives:

```
Input:  "roughly escarole"
Output: name: "escarole", preparation: "roughly"

Input:  "about 2 tablespoons butter"
Output: amount: "2", unit: "tablespoons", name: "butter"
```

### 5. Special Structure Handling

Rearranges special patterns for clarity:

```
Input:  "zest of 1 lime"
Output: name: "lime zest"

Input:  "juice of 1 lemon"
Output: name: "lemon juice"
```

### 6. Preparation Word Extraction

Separates preparation instructions from ingredient names:

**Extracted Prep Words:**
- Cutting: diced, chopped, minced, sliced, julienned, halved, quartered
- Processing: crushed, grated, shredded, beaten, whisked, melted, softened
- Modifiers: finely chopped, roughly chopped, thinly sliced, thickly sliced
- State: fresh, frozen, dried, canned, cooked, raw, toasted, roasted
- Cleaning: peeled, seeded, deveined, drained, rinsed, trimmed, stemmed

```
Input:  "1 cup diced yellow onion"
Output: amount: "1", unit: "cup", name: "yellow onion", preparation: "diced"
```

### 7. Invalid Fragment Filtering

Filters out incomplete or invalid ingredients:

**Filtered:**
- Just descriptors: "boneless", "bone-in", "cooked", "fresh"
- Incomplete phrases: "of choice", "to taste"
- Empty: "()", "( )"
- Too short: single characters

### 8. Clean Name Normalization

Cleans ingredient names while preserving specificity:

- Removes empty parentheses: `"onion ()"` → `"onion"`
- Removes parenthetical notes: `"parsley (fresh)"` → `"parsley"`
- Cleans extra whitespace
- Preserves specificity: `"yellow onion"` stays `"yellow onion"`

## Usage

### Parsing an Ingredient String

```php
use App\Services\IngredientService;

$service = new IngredientService();

$parsed = $service->parseIngredient("2 tablespoons olive oil, divided");

// Result:
[
    'name' => 'olive oil',
    'amount' => '2',
    'unit' => 'tablespoons',
    'preparation' => null,
    'notes' => 'divided',
    'original' => '2 tablespoons olive oil, divided'
]
```

### Finding or Creating Ingredients

Uses fuzzy matching with singular/plural variants:

```php
$ingredient = $service->findOrCreateIngredient("yellow onions");

// Finds existing "yellow onion" (singular)
// Or creates new ingredient with:
//   name: "yellow onions"
//   plural_name: "yellow onions" (using Laravel's Str::plural())
```

### Classification Normalization

Prepares ingredient name for dietary tag matching:

```php
$normalized = $service->normalizeForClassification("large fresh chicken breast");

// Returns: "chicken breast"
// (Removes: large, fresh)
```

## Re-parsing Existing Ingredients

If you've imported recipes before the new parsing system, use the re-parse command:

### Dry Run (Preview Changes)

```bash
php artisan ingredient:reparse --dry-run
```

Shows what would change without modifying the database:

```
Changed:
  From: 3 tablespoons unsalted butter
  To:   unsalted butter [amount: 3, unit: tablespoons]
```

### Apply Changes

```bash
php artisan ingredient:reparse
```

This command:
1. Reconstructs original ingredient strings from database parts
2. Re-parses using IngredientService
3. Updates ingredient names and pivot data
4. Merges duplicate ingredients
5. Deletes orphaned ingredients
6. Shows comprehensive summary

## Tuning the Parser

### Adding Units

Edit `app/Services/IngredientService.php`:

```php
protected array $units = [
    // Add your units here
    'smidgen', 'smidgens',  // Cooking measure
    'gill', 'gills',        // Old British measure
];
```

### Adding Preparation Words

```php
protected array $preparationWords = [
    // Add your prep words here
    'blanched',
    'sautéed',
    'caramelized',
];
```

### Modifying Special Structures

Edit the `handleSpecialStructures()` method:

```php
protected function handleSpecialStructures(string $text): string
{
    // Add patterns like "X of Y" where X is a part
    $pattern = '/^(zest|juice|peel|rind|leaves)\s+of\s+(.+)$/i';
    // ...
}
```

### Adding Invalid Fragments

```php
protected array $invalidFragments = [
    // Add fragments to filter out
    'optional',
    'as needed',
];
```

## Laravel Integration

### Using Laravel's Pluralizer

The service uses Laravel's `Str::plural()` and `Str::singular()` helpers instead of custom logic:

```php
use Illuminate\Support\Str;

Str::plural('tomato');    // tomatoes
Str::singular('knives');  // knife
Str::plural('fish');      // fish (handles irregular)
```

**Benefits:**
- Handles irregular plurals (knife → knives, fish → fish)
- Maintained by Laravel team
- More accurate than simple rules
- Supports multiple languages (if needed)

### No Plurals Persisted in Database

The system now uses a smarter approach:

**Old Way (❌):**
```php
Ingredient::create([
    'name' => 'tomato',
    'plural_name' => 'tomatoes',  // Stored in DB
]);
```

**New Way (✓):**
```php
Ingredient::create([
    'name' => 'tomato',
    'plural_name' => Str::plural('tomato'),  // Computed with Laravel
]);
```

## Common Parsing Patterns

### Before and After

| Original | Parsed Name | Amount | Unit | Prep |
|----------|-------------|--------|------|------|
| `3 tablespoons unsalted butter` | `unsalted butter` | 3 | tablespoons | - |
| `cup canned whole tomatoes` | `canned whole tomatoes` | - | cup | canned |
| `roughly escarole` | `escarole` | - | - | roughly |
| `lb. pork shoulder roast` | `pork shoulder roast` | - | lb | - |
| `zest of 1 lime` | `lime zest` | - | - | - |
| `3 to 4 lime leaves` | `lime leaves` | 4 | - | - |
| `cup/8 ounces whole-milk ricotta` | `whole-milk ricotta` | - | cup | - |
| `boneless` | *(filtered out)* | - | - | - |
| `sprig` | `sprig` | - | - | - |
| `2 teaspoons smoked paprika` | `smoked paprika` | 2 | teaspoons | - |
| `of 1 lime` | *(filtered out)* | - | - | - |
| `parsley ()` | `parsley` | - | - | - |

## Testing

### Unit Tests

Create tests for the service:

```php
// tests/Unit/IngredientServiceTest.php

public function test_parses_amount_and_unit()
{
    $service = new IngredientService();
    $result = $service->parseIngredient('2 tablespoons olive oil');

    $this->assertEquals('olive oil', $result['name']);
    $this->assertEquals('2', $result['amount']);
    $this->assertEquals('tablespoons', $result['unit']);
}

public function test_handles_ranges()
{
    $service = new IngredientService();
    $result = $service->parseIngredient('3-4 lime leaves');

    $this->assertEquals('lime leaves', $result['name']);
    $this->assertEquals('4', $result['amount']);
}

public function test_removes_leading_adjectives()
{
    $service = new IngredientService();
    $result = $service->parseIngredient('roughly escarole');

    $this->assertEquals('escarole', $result['name']);
    $this->assertEquals('roughly', $result['preparation']);
}
```

### Manual Testing

```bash
# Test parsing directly
php artisan tinker

$service = new App\Services\IngredientService();
$service->parseIngredient("2 tablespoons chile crisp");
```

## Troubleshooting

### Issue: Ingredient not parsing correctly

**Solution:** Check the order of operations:
1. Amount extraction (first)
2. Unit extraction (second)
3. Special structures (third)
4. Preparation extraction (fourth)
5. Name cleaning (last)

### Issue: Valid ingredient being filtered out

**Solution:** Check `invalidFragments` array - remove entries that are legitimate ingredients

### Issue: Unit not being detected

**Solution:** Add unit to `$units` array with all variations (plural, abbreviations)

### Issue: Preparation word stays in name

**Solution:** Add to `$preparationWords` array

### Issue: Classification not working

**Solution:** Use `normalizeForClassification()` which removes size descriptors and adjectives before matching

## Future Enhancements

### Machine Learning Classification

Train a model on classified ingredients:

```php
// Future: ML-based classification
$classification = MachineLearningService::classifyIngredient($ingredientName);
```

### API Integration

Use nutrition databases for automatic classification:

```php
// Future: USDA FoodData Central API
$nutritionData = NutritionApiService::lookup($ingredientName);
$allergens = $nutritionData['allergens'];
```

### Crowdsourced Corrections

Allow users to correct parsing:

```php
// Future: User feedback loop
$correction = UserCorrection::where('original', $originalString)
    ->where('votes', '>', 5)
    ->first();
```

### Ingredient Synonyms

Handle regional variations:

```php
// Future: Synonym detection
protected array $synonyms = [
    'cilantro' => 'coriander',
    'arugula' => 'rocket',
    'zucchini' => 'courgette',
];
```

## Performance Considerations

- **Caching**: Consider caching parsed results for frequently used ingredients
- **Batch Processing**: Re-parsing command processes in batches for memory efficiency
- **Database Indexes**: Ensure indexes on `ingredients.name` for fast lookups

## References

- Laravel String Helpers: https://laravel.com/docs/10.x/helpers#strings
- Schema.org Recipe: https://schema.org/Recipe
- USDA FoodData Central: https://fdc.nal.usda.gov/
