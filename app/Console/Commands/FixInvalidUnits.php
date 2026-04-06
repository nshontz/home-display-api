<?php

namespace App\Console\Commands;

use App\Models\RecipeIngredient;
use App\Services\IngredientService;
use Illuminate\Console\Command;
use ReflectionClass;

class FixInvalidUnits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingredient:fix-invalid-units {--dry-run : Preview changes without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix ingredients with invalid units (not in the defined units list)';

    protected IngredientService $ingredientService;
    protected array $validUnits;

    public function __construct()
    {
        parent::__construct();
        $this->ingredientService = new IngredientService();

        // Get the valid units list from IngredientService
        $reflection = new ReflectionClass($this->ingredientService);
        $property = $reflection->getProperty('units');
        $property->setAccessible(true);
        $this->validUnits = array_map('strtolower', $property->getValue($this->ingredientService));
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

        // Find all recipe ingredients with units
        $this->info('Checking all ingredients with units...');

        $invalidIngredients = RecipeIngredient::whereNotNull('unit')
            ->where('unit', '!=', '')
            ->with(['ingredient', 'recipe'])
            ->get()
            ->filter(function ($ri) {
                return !in_array(strtolower($ri->unit), $this->validUnits);
            });

        $count = $invalidIngredients->count();

        if ($count === 0) {
            $this->info('No ingredients with invalid units found!');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} ingredients with invalid units");
        $this->newLine();

        $fixed = 0;
        $failed = 0;

        // Group by unit to show summary
        $byUnit = $invalidIngredients->groupBy('unit');

        $this->table(
            ['Invalid Unit', 'Count'],
            $byUnit->map(fn($items, $unit) => [$unit, $items->count()])->sortByDesc(1)->values()
        );
        $this->newLine();

        if (!$this->confirm('Do you want to fix these?', true)) {
            return Command::SUCCESS;
        }

        $this->newLine();

        foreach ($invalidIngredients as $recipeIngredient) {
            // For these cases, the "unit" is actually part of the ingredient name
            // Example: "8 garlic cloves" parsed as amount=8, unit=garlic, name=cloves
            // Should be: amount=8, unit=null, name=garlic cloves

            $newName = trim($recipeIngredient->unit . ' ' . $recipeIngredient->ingredient->name);

            $this->line("Recipe: {$recipeIngredient->recipe->title}");
            $this->line("  Current: {$recipeIngredient->amount} [{$recipeIngredient->unit}] {$recipeIngredient->ingredient->name}");
            $this->line("  New:     {$recipeIngredient->amount} [] {$newName}");

            if (!$dryRun) {
                // Find or create ingredient with corrected name
                $newIngredient = $this->ingredientService->findOrCreateIngredient($newName);

                $recipeIngredient->update([
                    'ingredient_id' => $newIngredient->id,
                    'unit' => null,
                ]);
                $this->info("  ✓ Fixed");
            } else {
                $this->comment("  → Would fix (dry run)");
            }

            $fixed++;
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
}
