<?php

namespace App\Console\Commands;

use App\Models\Dinner;
use App\Models\Recipe;
use Illuminate\Console\Command;

class SuggestRecipesForDinners extends Command
{
    protected $signature = 'dinner:suggest-recipes
                            {--auto-link : Automatically link dinners with exact title matches}
                            {--threshold=80 : Similarity threshold percentage (0-100)}';

    protected $description = 'Suggest recipe matches for dinners based on title similarity';

    public function handle()
    {
        $autoLink = $this->option('auto-link');
        $threshold = (int) $this->option('threshold');

        $this->info('Finding dinners without linked recipes...');
        $this->newLine();

        // Get dinners without recipes
        $dinners = Dinner::whereNull('recipe_id')->get();

        if ($dinners->isEmpty()) {
            $this->info('✓ All dinners are already linked to recipes!');
            return Command::SUCCESS;
        }

        $this->info("Found {$dinners->count()} dinners without recipes");
        $this->newLine();

        $recipes = Recipe::all();

        if ($recipes->isEmpty()) {
            $this->warn('No recipes found. Import some recipes first!');
            return Command::FAILURE;
        }

        $stats = [
            'exact_matches' => 0,
            'similar_matches' => 0,
            'no_matches' => 0,
            'auto_linked' => 0,
        ];

        $suggestions = [];

        foreach ($dinners as $dinner) {
            $match = $this->findBestMatch($dinner->title, $recipes, $threshold);

            if (!$match) {
                $stats['no_matches']++;
                continue;
            }

            if ($match['similarity'] === 100) {
                $stats['exact_matches']++;

                if ($autoLink) {
                    $dinner->recipe_id = $match['recipe']->id;
                    $dinner->save();
                    $stats['auto_linked']++;
                    $this->line("  ✓ Auto-linked: '{$dinner->title}' → '{$match['recipe']->title}'");
                } else {
                    $suggestions[] = $match;
                }
            } else {
                $stats['similar_matches']++;
                $suggestions[] = $match;
            }
        }

        $this->newLine();

        // Show suggestions
        if (!empty($suggestions)) {
            $this->info('Recipe Suggestions:');
            $this->newLine();

            foreach ($suggestions as $match) {
                $similarity = $match['similarity'];
                $color = $similarity === 100 ? 'info' : 'comment';

                $this->{$color}("  Dinner: '{$match['dinner']->title}'");
                $this->{$color}("  Recipe: '{$match['recipe']->title}' ({$similarity}% match)");
                $this->line("  ID: {$match['dinner']->id} → {$match['recipe']->id}");
                $this->newLine();
            }

            $this->newLine();
            $this->info('To link these manually, use the Filament admin panel:');
            $this->line('  Visit: /admin/dinners');
            $this->newLine();

            if (!$autoLink && $stats['exact_matches'] > 0) {
                $this->info('To automatically link exact matches, run:');
                $this->line('  php artisan dinner:suggest-recipes --auto-link');
                $this->newLine();
            }
        }

        // Show summary
        $this->info('╔════════════════════════════════════════════════╗');
        $this->info('║          Recipe Suggestion Summary             ║');
        $this->info('╚════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("  Exact matches:       {$stats['exact_matches']}");
        $this->line("  Similar matches:     {$stats['similar_matches']}");
        $this->line("  No matches:          {$stats['no_matches']}");
        if ($autoLink) {
            $this->line("  Auto-linked:         {$stats['auto_linked']}");
        }
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Find the best matching recipe for a dinner title
     */
    protected function findBestMatch(string $dinnerTitle, $recipes, int $threshold): ?array
    {
        $bestMatch = null;
        $bestSimilarity = 0;

        $normalizedDinner = $this->normalizeTitle($dinnerTitle);

        foreach ($recipes as $recipe) {
            $normalizedRecipe = $this->normalizeTitle($recipe->title);

            // Check exact match first
            if ($normalizedDinner === $normalizedRecipe) {
                return [
                    'dinner' => Dinner::where('title', $dinnerTitle)->first(),
                    'recipe' => $recipe,
                    'similarity' => 100,
                ];
            }

            // Calculate similarity
            similar_text($normalizedDinner, $normalizedRecipe, $percent);

            if ($percent > $bestSimilarity && $percent >= $threshold) {
                $bestSimilarity = $percent;
                $bestMatch = $recipe;
            }
        }

        if ($bestMatch) {
            return [
                'dinner' => Dinner::where('title', $dinnerTitle)->first(),
                'recipe' => $bestMatch,
                'similarity' => round($bestSimilarity),
            ];
        }

        return null;
    }

    /**
     * Normalize title for comparison
     */
    protected function normalizeTitle(string $title): string
    {
        $title = strtolower($title);

        // Remove common prefixes
        $title = preg_replace('/^(recipe:\s*|recipe\s*-\s*)/i', '', $title);

        // Remove punctuation
        $title = preg_replace('/[^\w\s]/', '', $title);

        // Remove extra whitespace
        $title = preg_replace('/\s+/', ' ', $title);

        return trim($title);
    }
}
