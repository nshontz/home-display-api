<?php

namespace App\Console\Commands;

use App\Models\RecipeIngredient;
use App\Services\IngredientService;
use Illuminate\Console\Command;

class FixRangeIngredients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingredient:fix-ranges {--dry-run : Preview changes without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix ingredients with unit "to" which indicates failed range parsing';

    protected IngredientService $ingredientService;

    public function __construct()
    {
        parent::__construct();
        $this->ingredientService = new IngredientService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be saved');
            $this->newLine();
        }

        // Find all recipe ingredients with unit 'to'
        $problematicIngredients = RecipeIngredient::where('unit', 'to')
            ->with(['ingredient', 'recipe'])
            ->get();

        $count = $problematicIngredients->count();

        if ($count === 0) {
            $this->info('No ingredients with unit "to" found!');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} ingredients with unit 'to'");
        $this->newLine();

        $fixed = 0;
        $failed = 0;

        foreach ($problematicIngredients as $recipeIngredient) {
            // Reconstruct original string
            $originalString = $this->reconstructOriginalString($recipeIngredient);

            $this->line("Recipe: {$recipeIngredient->recipe->title}");
            $this->line("  Current: {$recipeIngredient->amount} {$recipeIngredient->unit} {$recipeIngredient->ingredient->name}");
            $this->line("  Reconstructed: {$originalString}");

            // Re-parse with current IngredientService
            $parsed = $this->ingredientService->parseIngredient($originalString);

            if (!$parsed) {
                $this->error("  ✗ Failed to parse");
                $failed++;
                $this->newLine();
                continue;
            }

            // Find or create ingredient with corrected name
            $newIngredient = $this->ingredientService->findOrCreateIngredient($parsed['name']);

            $this->line("  New parsed: {$parsed['amount']} {$parsed['unit']} {$newIngredient->name}");

            if (!$dryRun) {
                $recipeIngredient->update([
                    'ingredient_id' => $newIngredient->id,
                    'amount' => $parsed['amount'],
                    'unit' => $parsed['unit'],
                    'preparation' => $parsed['preparation'],
                ]);
                $this->info("  ✓ Fixed");
            } else {
                $this->comment("  → Would fix (dry run)");
            }

            $fixed++;
            $this->newLine();
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Fixed: {$fixed}");
        if ($failed > 0) {
            $this->line("  Failed: {$failed}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }

    /**
     * Reconstruct original ingredient string from database fields
     */
    protected function reconstructOriginalString(RecipeIngredient $recipeIngredient): string
    {
        $parts = [];

        // Reconstruct "amount to [amount+2] unit ingredient"
        // Since we stored the upper bound, we can infer the range was probably "[amount-2] to [amount]"
        if ($recipeIngredient->amount) {
            $lowerBound = max(1, $recipeIngredient->amount - 2); // Guess lower bound
            $parts[] = $lowerBound . ' to ' . $recipeIngredient->amount;
        }

        // Add ingredient name (skip unit 'to' since that's the problem)
        $parts[] = $recipeIngredient->ingredient->name;

        if ($recipeIngredient->preparation) {
            $parts[] = $recipeIngredient->preparation;
        }

        return implode(' ', $parts);
    }
}
