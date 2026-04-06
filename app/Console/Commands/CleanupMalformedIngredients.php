<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupMalformedIngredients extends Command
{
    protected $signature = 'ingredient:cleanup-malformed {--dry-run : Preview changes without updating} {--delete-unused : Also delete completely unused ingredients}';

    protected $description = 'Clean up malformed ingredient names (parentheses, slashes, fragments)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $deleteUnused = $this->option('delete-unused');

        if ($dryRun) {
            $this->warn('⚠ DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('Finding malformed ingredients...');
        $this->newLine();

        // Find malformed ingredients
        $malformed = Ingredient::where(function ($query) {
            $query->where('name', 'LIKE', '%(%')
                  ->orWhere('name', 'LIKE', '%)%')
                  ->orWhere('name', 'LIKE', '%/%')
                  ->orWhere('name', 'LIKE', '%(%)%')
                  ->orWhere('name', '=', ')')
                  ->orWhere('name', '=', '(')
                  ->orWhere('name', 'LIKE', '/ %')
                  ->orWhere('name', 'LIKE', 'cups/%')
                  ->orWhere('name', 'LIKE', 'ounces/%')
                  ->orWhere('name', 'LIKE', '% to taste')
                  ->orWhere('name', 'LIKE', '% of choice');
        })->withCount('recipeIngredients')->get();

        if ($malformed->isEmpty()) {
            $this->info('✓ No malformed ingredients found!');
            return Command::SUCCESS;
        }

        $this->warn("Found {$malformed->count()} malformed ingredients");
        $this->newLine();

        $stats = [
            'cleaned' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ];

        foreach ($malformed as $ingredient) {
            $oldName = $ingredient->name;
            $usageCount = $ingredient->recipe_ingredients_count;

            // Try to clean the name
            $cleanedName = $this->cleanIngredientName($oldName);

            // If cleaning didn't help or it's empty, and it's unused, we can delete it
            if (empty($cleanedName) || $cleanedName === $oldName) {
                if ($usageCount === 0 && $deleteUnused) {
                    $this->line("  Delete: '{$oldName}' (unused)");
                    if (!$dryRun) {
                        $ingredient->delete();
                        $stats['deleted']++;
                    }
                } else {
                    $this->line("  Skip: '{$oldName}' (can't clean, used {$usageCount} times)");
                    $stats['skipped']++;
                }
                continue;
            }

            $this->line("  Clean: '{$oldName}' → '{$cleanedName}'");

            if (!$dryRun) {
                // Find or create the cleaned ingredient
                $cleanIngredient = Ingredient::where('name', $cleanedName)->first();

                if (!$cleanIngredient) {
                    // Rename this ingredient
                    $ingredient->update([
                        'name' => $cleanedName,
                        'plural_name' => \Illuminate\Support\Str::plural($cleanedName),
                    ]);
                    $stats['cleaned']++;
                } else {
                    // Merge with existing clean ingredient
                    RecipeIngredient::where('ingredient_id', $ingredient->id)
                        ->update(['ingredient_id' => $cleanIngredient->id]);

                    $ingredient->delete();
                    $stats['cleaned']++;
                    $this->line("    (merged with existing ingredient)");
                }
            }
        }

        $this->newLine();
        $this->info('╔════════════════════════════════════════════════╗');
        $this->info('║       Malformed Ingredient Cleanup Summary     ║');
        $this->info('╚════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("  Cleaned/merged:        {$stats['cleaned']}");
        $this->line("  Deleted:               {$stats['deleted']}");
        $this->line("  Skipped:               {$stats['skipped']}");
        $this->newLine();

        if ($dryRun) {
            $this->warn('This was a DRY RUN. Run without --dry-run to apply changes.');
        } else {
            $this->info('✓ Cleanup complete!');
        }

        return Command::SUCCESS;
    }

    /**
     * Clean ingredient name
     */
    protected function cleanIngredientName(string $name): string
    {
        // Remove leading/trailing slashes
        $name = trim($name, '/ ');

        // Remove empty parentheses
        $name = preg_replace('/\(\s*\)/', '', $name);

        // Remove trailing "to taste", "of choice"
        $name = preg_replace('/\s+(to taste|of choice)\s*$/i', '', $name);

        // Clean up compound units like "cups/8 ounces" to just the ingredient after
        if (preg_match('/^(cups?|ounces?|oz|lb|lbs?|tablespoons?|tbsp|teaspoons?|tsp)\/[\d\s]+\s*(?:cups?|ounces?|oz|lb|lbs?|tablespoons?|tbsp|teaspoons?|tsp)\s+(.+)$/i', $name, $matches)) {
            $name = $matches[2];
        }

        // Remove incomplete parenthetical expressions
        $name = preg_replace('/\(\s*[a-z]*\s*$/i', '', $name);  // "onion (" → "onion"
        $name = preg_replace('/^\s*[a-z]*\s*\)/i', '', $name);  // ")" → ""

        // Remove incomplete forward slash expressions
        $name = preg_replace('/\/\s*[^\s]+\s*\)/', '', $name);  // "/ something )" → ""

        // Clean up extra whitespace
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);

        // If it's just punctuation, return empty
        if (preg_match('/^[\(\)\[\]\{\}\/,\.\s]+$/', $name)) {
            return '';
        }

        // If it's too short, return empty
        if (strlen($name) < 3) {
            return '';
        }

        return $name;
    }
}
