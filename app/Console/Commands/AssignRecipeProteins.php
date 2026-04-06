<?php

namespace App\Console\Commands;

use App\Models\Protein;
use App\Models\Recipe;
use Illuminate\Console\Command;

class AssignRecipeProteins extends Command
{
    protected $signature = 'recipe:assign-proteins {--dry-run : Preview changes}';
    protected $description = 'Auto-assign proteins to recipes based on ingredients';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE');
            $this->newLine();
        }

        // Get all proteins
        $proteins = Protein::all()->keyBy('name');

        // Define protein keywords to search for in ingredients
        $proteinKeywords = [
            'Chicken' => ['chicken', 'poultry'],
            'Beef' => ['beef', 'steak', 'brisket', 'sirloin', 'ribeye', 'chuck', 'ground beef'],
            'Pork' => ['pork', 'bacon', 'ham', 'sausage', 'prosciutto', 'pancetta', 'chorizo', 'salami', 'pepperoni'],
            'Turkey' => ['turkey'],
            'Lamb' => ['lamb'],
            'Venison' => ['venison', 'deer'],
            'Seafood' => ['fish', 'salmon', 'tuna', 'shrimp', 'prawn', 'scallop', 'lobster', 'crab', 'cod', 'halibut', 'tilapia', 'anchovy', 'sardine'],
            'Tofu' => ['tofu', 'tempeh'],
            'Legume' => ['beans', 'lentils', 'chickpea', 'black bean', 'kidney bean', 'pinto bean', 'navy bean'],
        ];

        $recipes = Recipe::with(['recipeIngredients.ingredient'])->get();
        $assigned = 0;
        $vegetarian = 0;

        foreach ($recipes as $recipe) {
            // Get recipe title and all ingredient names
            $title = strtolower($recipe->title);
            $ingredientNames = $recipe->recipeIngredients
                ->pluck('ingredient.name')
                ->map(fn($name) => strtolower($name))
                ->join(' ');

            $foundProtein = null;

            // First check the recipe title for protein keywords (more reliable)
            foreach ($proteinKeywords as $proteinName => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($title, strtolower($keyword))) {
                        $foundProtein = $proteinName;
                        break 2;
                    }
                }
            }

            // If not found in title, check ingredients
            if (!$foundProtein) {
                foreach ($proteinKeywords as $proteinName => $keywords) {
                    foreach ($keywords as $keyword) {
                        if (str_contains($ingredientNames, strtolower($keyword))) {
                            $foundProtein = $proteinName;
                            break 2;
                        }
                    }
                }
            }

            if ($foundProtein && isset($proteins[$foundProtein])) {
                $this->line($recipe->title);
                $this->info("  → {$foundProtein}");

                if (!$dryRun) {
                    $recipe->protein_id = $proteins[$foundProtein]->id;
                    $recipe->save();
                }

                $assigned++;
            } else {
                // No protein found - likely vegetarian
                if (isset($proteins['Vegetarian'])) {
                    $this->line($recipe->title);
                    $this->comment("  → Vegetarian (no protein detected)");

                    if (!$dryRun) {
                        $recipe->protein_id = $proteins['Vegetarian']->id;
                        $recipe->save();
                    }

                    $vegetarian++;
                }
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Assigned specific protein: {$assigned}");
        $this->line("  Assigned vegetarian: {$vegetarian}");
        $this->line("  Total: " . ($assigned + $vegetarian));

        if ($dryRun) {
            $this->newLine();
            $this->warn('Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }
}
