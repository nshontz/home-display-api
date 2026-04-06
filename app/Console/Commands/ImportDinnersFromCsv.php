<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\RecipeFactory;
use App\Services\RecipeImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportDinnersFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipe:import-csv {file=documentation/dinners.csv} {--skip-existing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import recipes from dinners CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path($this->argument('file'));

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Reading CSV from: {$filePath}");

        $csv = array_map('str_getcsv', file($filePath));
        $headers = array_shift($csv); // Remove header row

        $total = count($csv);
        $imported = 0;
        $skipped = 0;
        $failed = 0;

        $importService = new RecipeImportService();

        $this->info("Found {$total} dinners in CSV");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);

        foreach ($csv as $row) {
            $data = array_combine($headers, $row);

            $title = $data['title'] ?? null;
            $url = $data['recipe_url'] ?? null;

            if (empty($url)) {
                $this->warn("  ⊘ Skipping '{$title}' - no URL");
                $skipped++;
                $progressBar->advance();
                continue;
            }

            // Check if already imported
            if ($this->option('skip-existing')) {
                $exists = Recipe::where('source_url', $url)->exists();
                if ($exists) {
                    $this->info("  ⊘ Skipping '{$title}' - already imported");
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }
            }

            try {
                $recipeData = $importService->importFromUrl($url);
                $recipe = RecipeFactory::create($recipeData);

                $this->info("  ✓ Imported: {$recipe->title} ({$recipe->recipeIngredients->count()} ingredients)");
                $imported++;

            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$title}");
                $this->line("    Error: {$e->getMessage()}");
                $failed++;
                Log::error("Failed to import recipe from {$url}: {$e->getMessage()}");
            }

            $progressBar->advance();

            // Small delay to be respectful to servers
            usleep(500000); // 0.5 seconds
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Import complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported', $imported],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Total', $total],
            ]
        );

        return Command::SUCCESS;
    }
}
