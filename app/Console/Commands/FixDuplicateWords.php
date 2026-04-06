<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use App\Services\IngredientService;
use Illuminate\Console\Command;

class FixDuplicateWords extends Command
{
    protected $signature = 'ingredient:fix-duplicates {--dry-run : Preview changes}';
    protected $description = 'Fix ingredients with duplicate consecutive words';

    protected IngredientService $ingredientService;

    public function __construct()
    {
        parent::__construct();
        $this->ingredientService = new IngredientService();
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE');
            $this->newLine();
        }

        $ingredients = Ingredient::all();
        $duplicates = [];

        // Find ingredients with duplicate consecutive words
        foreach ($ingredients as $ing) {
            if (preg_match('/\b(\w+)\s+\1\b/i', $ing->name)) {
                $duplicates[] = $ing;
            }
        }

        if (empty($duplicates)) {
            $this->info('No duplicate words found!');
            return Command::SUCCESS;
        }

        $this->info('Found ' . count($duplicates) . ' ingredients with duplicate words');
        $this->newLine();

        $fixed = 0;

        foreach ($duplicates as $ingredient) {
            // Remove duplicate consecutive words
            $newName = preg_replace('/\b(\w+)\s+\1\b/i', '$1', $ingredient->name);
            $newName = trim($newName);

            $this->line("Old: {$ingredient->name}");
            $this->line("New: {$newName}");

            if (!$dryRun) {
                // Update all recipe ingredients using this ingredient
                $recipeIngredients = RecipeIngredient::where('ingredient_id', $ingredient->id)->get();

                // Find or create the corrected ingredient
                $newIngredient = $this->ingredientService->findOrCreateIngredient($newName);

                // Update all references
                foreach ($recipeIngredients as $ri) {
                    $ri->ingredient_id = $newIngredient->id;
                    $ri->save();
                }

                $this->info("  ✓ Fixed " . $recipeIngredients->count() . " recipe ingredient(s)");
            } else {
                $count = RecipeIngredient::where('ingredient_id', $ingredient->id)->count();
                $this->comment("  → Would fix {$count} recipe ingredient(s)");
            }

            $fixed++;
            $this->newLine();
        }

        $this->info("Summary: {$fixed} ingredients with duplicates");

        if ($dryRun) {
            $this->newLine();
            $this->warn('Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
    }
}
