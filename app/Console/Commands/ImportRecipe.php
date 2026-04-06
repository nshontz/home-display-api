<?php

namespace App\Console\Commands;

use App\Services\RecipeFactory;
use App\Services\RecipeImportService;
use Illuminate\Console\Command;

class ImportRecipe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipe:import {url : The URL of the recipe to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a recipe from a URL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');

        $this->info("Importing recipe from: {$url}");

        try {
            // Import recipe data from URL
            $importService = new RecipeImportService();
            $recipeData = $importService->importFromUrl($url);

            $this->info("Recipe data extracted: {$recipeData['title']}");
            $this->line("  Source: {$recipeData['source_name']}");
            $this->line("  Servings: " . ($recipeData['servings'] ?? 'N/A'));
            $this->line("  Ingredients: " . count($recipeData['ingredients']));
            $this->line("  Steps: " . count($recipeData['steps']));

            // Create recipe in database
            $this->info("Creating recipe in database...");
            $recipe = RecipeFactory::create($recipeData);

            $this->info("✓ Successfully imported recipe!");
            $this->line("  Recipe ID: {$recipe->id}");
            $this->line("  Title: {$recipe->title}");
            $this->line("  Ingredients: {$recipe->recipeIngredients->count()}");
            $this->line("  Steps: {$recipe->steps->count()}");

            if ($recipe->image_path) {
                $this->line("  Image: ✓ Downloaded");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to import recipe: {$e->getMessage()}");
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
