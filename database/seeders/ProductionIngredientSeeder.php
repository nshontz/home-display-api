<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientTag;
use App\Services\IngredientService;
use Illuminate\Database\Seeder;

class ProductionIngredientSeeder extends Seeder
{
    protected IngredientService $ingredientService;

    public function __construct()
    {
        parent::__construct();
        $this->ingredientService = new IngredientService();
    }

    /**
     * Seed ingredient tags based on ingredient names
     */
    public function run(): void
    {
        $this->command->info('Classifying ingredients with dietary tags...');

        // Get all tags
        $tags = [
            'gluten-free' => IngredientTag::where('slug', 'gluten-free')->first(),
            'dairy' => IngredientTag::where('slug', 'dairy')->first(),
            'vegan' => IngredientTag::where('slug', 'vegan')->first(),
            'vegetarian' => IngredientTag::where('slug', 'vegetarian')->first(),
            'nut-allergen' => IngredientTag::where('slug', 'nut-allergen')->first(),
            'shellfish' => IngredientTag::where('slug', 'shellfish')->first(),
            'soy' => IngredientTag::where('slug', 'soy')->first(),
            'egg' => IngredientTag::where('slug', 'egg')->first(),
            'fish' => IngredientTag::where('slug', 'fish')->first(),
        ];

        // Classification rules
        $classifications = [
            // Dairy products
            'dairy' => [
                'butter', 'cream', 'milk', 'cheese', 'parmesan', 'mozzarella', 'cheddar', 'ricotta',
                'feta', 'goat cheese', 'yogurt', 'sour cream', 'crème fraîche', 'mascarpone',
                'gruyere', 'pecorino', 'asiago', 'fontina', 'provolone', 'brie', 'camembert'
            ],

            // Eggs
            'egg' => [
                'egg', 'eggs', 'yolk', 'white', 'mayonnaise'
            ],

            // Fish
            'fish' => [
                'fish', 'salmon', 'tuna', 'cod', 'halibut', 'tilapia', 'trout', 'anchovy',
                'anchovies', 'sardine', 'herring', 'mackerel', 'bass', 'snapper'
            ],

            // Shellfish
            'shellfish' => [
                'shrimp', 'prawn', 'lobster', 'crab', 'crayfish', 'clam', 'mussel',
                'oyster', 'scallop', 'crawfish'
            ],

            // Nuts
            'nut-allergen' => [
                'almond', 'walnut', 'pecan', 'cashew', 'pistachio', 'hazelnut', 'macadamia',
                'peanut', 'peanuts', 'pine nut', 'chestnut', 'brazil nut'
            ],

            // Soy
            'soy' => [
                'soy sauce', 'tofu', 'tempeh', 'miso', 'edamame', 'soybean', 'tamari'
            ],

            // Gluten-containing (items to EXCLUDE from gluten-free)
            'gluten' => [
                'flour', 'wheat', 'bread', 'pasta', 'noodle', 'spaghetti', 'fettuccine',
                'linguine', 'penne', 'rigatoni', 'macaroni', 'orzo', 'couscous', 'bulgur',
                'seitan', 'ramen', 'udon', 'soba', 'panko', 'breadcrumb', 'crouton',
                'bagel', 'tortilla', 'pita', 'naan', 'bun', 'roll', 'biscuit', 'croissant',
                'muffin', 'cake', 'cookie', 'cracker', 'pretzel', 'wafer', 'dough',
                'beer', 'ale', 'lager', 'wheat beer', 'barley', 'rye', 'gnocchi', 'dumpling'
            ],
        ];

        // Mark all ingredients as gluten-free by default, then remove the tag from gluten-containing items
        $ingredients = Ingredient::all();
        $total = $ingredients->count();
        $progress = $this->command->getOutput()->createProgressBar($total);

        foreach ($ingredients as $ingredient) {
            // Normalize for classification (removes size descriptors, adjectives)
            $name = $this->ingredientService->normalizeForClassification($ingredient->name);
            $appliedTags = [];

            // Check each classification
            foreach ($classifications as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($name, $keyword)) {
                        if ($category === 'gluten') {
                            // Don't tag as gluten, but skip gluten-free tag
                            $appliedTags['contains-gluten'] = true;
                        } else {
                            $appliedTags[$category] = true;
                        }
                        break;
                    }
                }
            }

            // Apply gluten-free tag if no gluten detected
            if (!isset($appliedTags['contains-gluten'])) {
                // Default gluten-free for most whole ingredients
                if ($tags['gluten-free'] && !$ingredient->tags->contains($tags['gluten-free'])) {
                    $ingredient->tags()->attach($tags['gluten-free']);
                }
            }

            // Apply other tags
            foreach ($appliedTags as $tagKey => $value) {
                if ($tagKey === 'contains-gluten') continue;

                if (isset($tags[$tagKey]) && !$ingredient->tags->contains($tags[$tagKey])) {
                    $ingredient->tags()->attach($tags[$tagKey]);
                }
            }

            // Vegetarian logic: everything except meat/fish/shellfish
            $isMeat = str_contains($name, 'chicken') || str_contains($name, 'beef') ||
                      str_contains($name, 'pork') || str_contains($name, 'lamb') ||
                      str_contains($name, 'veal') || str_contains($name, 'duck') ||
                      str_contains($name, 'turkey') || str_contains($name, 'sausage') ||
                      str_contains($name, 'bacon') || str_contains($name, 'ham') ||
                      str_contains($name, 'prosciutto') || str_contains($name, 'salami') ||
                      str_contains($name, 'pepperoni') || str_contains($name, 'chorizo') ||
                      str_contains($name, 'venison') || str_contains($name, 'bison') ||
                      str_contains($name, 'rabbit') || str_contains($name, 'quail');

            $isSeafood = isset($appliedTags['fish']) || isset($appliedTags['shellfish']);

            if (!$isMeat && !$isSeafood) {
                if ($tags['vegetarian'] && !$ingredient->tags->contains($tags['vegetarian'])) {
                    $ingredient->tags()->attach($tags['vegetarian']);
                }
            }

            // Vegan logic: vegetarian + no dairy + no eggs + no honey
            $isDairy = isset($appliedTags['dairy']);
            $isEgg = isset($appliedTags['egg']);
            $isHoney = str_contains($name, 'honey');

            if (!$isMeat && !$isSeafood && !$isDairy && !$isEgg && !$isHoney) {
                if ($tags['vegan'] && !$ingredient->tags->contains($tags['vegan'])) {
                    $ingredient->tags()->attach($tags['vegan']);
                }
            }

            $progress->advance();
        }

        $progress->finish();
        $this->command->newLine(2);

        // Show summary
        $this->command->info('Ingredient classification complete!');
        $this->command->newLine();

        foreach ($tags as $slug => $tag) {
            if ($tag) {
                $count = $tag->ingredients()->count();
                $this->command->line("  {$tag->name}: {$count} ingredients");
            }
        }
    }
}
