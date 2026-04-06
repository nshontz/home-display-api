<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\IngredientTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportIngredientSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ingredient:export-seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export current ingredients with tags to a production seeder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exporting ingredients with classifications...');

        $ingredients = Ingredient::with('tags')->orderBy('name')->get();
        $total = $ingredients->count();

        $this->info("Found {$total} ingredients to export");

        // Build the seeder content
        $content = $this->generateSeederContent($ingredients);

        // Write to seeder file
        $path = database_path('seeders/ClassifiedIngredientDataSeeder.php');
        File::put($path, $content);

        $this->info("✓ Seeder created: {$path}");
        $this->line("  {$total} ingredients exported with tags");

        $this->newLine();
        $this->info('To use in production, run:');
        $this->line('  php artisan db:seed --class=IngredientTagSeeder');
        $this->line('  php artisan db:seed --class=ClassifiedIngredientDataSeeder');

        return Command::SUCCESS;
    }

    protected function generateSeederContent($ingredients): string
    {
        $ingredientData = [];
        $tagMappings = [];

        foreach ($ingredients as $ingredient) {
            $ingredientData[] = [
                'name' => $ingredient->name,
                'category' => $ingredient->category,
            ];

            if ($ingredient->tags->isNotEmpty()) {
                $tagMappings[$ingredient->name] = $ingredient->tags->pluck('slug')->toArray();
            }
        }

        $ingredientDataStr = var_export($ingredientData, true);
        $tagMappingsStr = var_export($tagMappings, true);

        return <<<PHP
<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientTag;
use Illuminate\Database\Seeder;

class ClassifiedIngredientDataSeeder extends Seeder
{
    /**
     * Seed classified ingredients with tags
     *
     * This seeder was auto-generated from production data
     * Total ingredients: {$ingredients->count()}
     */
    public function run(): void
    {
        \$this->command->info('Seeding {$ingredients->count()} pre-classified ingredients...');

        // Get all tags
        \$tags = IngredientTag::all()->keyBy('slug');

        // Ingredient data
        \$ingredients = {$ingredientDataStr};

        // Tag mappings (ingredient name => [tag slugs])
        \$tagMappings = {$tagMappingsStr};

        \$progress = \$this->command->getOutput()->createProgressBar(count(\$ingredients));

        foreach (\$ingredients as \$ingredientData) {
            \$ingredient = Ingredient::firstOrCreate(
                ['name' => \$ingredientData['name']],
                [
                    'category' => \$ingredientData['category'],
                ]
            );

            // Attach tags if they exist for this ingredient
            if (isset(\$tagMappings[\$ingredient->name])) {
                \$tagIds = [];
                foreach (\$tagMappings[\$ingredient->name] as \$tagSlug) {
                    if (isset(\$tags[\$tagSlug])) {
                        \$tagIds[] = \$tags[\$tagSlug]->id;
                    }
                }

                if (!empty(\$tagIds)) {
                    \$ingredient->tags()->syncWithoutDetaching(\$tagIds);
                }
            }

            \$progress->advance();
        }

        \$progress->finish();
        \$this->command->newLine(2);

        // Show summary
        \$this->command->info('Ingredient import complete!');
        \$this->command->newLine();

        foreach (\$tags as \$tag) {
            \$count = \$tag->ingredients()->count();
            \$this->command->line("  {\$tag->name}: {\$count} ingredients");
        }
    }
}

PHP;
    }
}

