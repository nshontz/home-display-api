<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeCollection;
use App\Models\RecipeLayout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RecipePDFService
{
    /**
     * Generate PDF for a single recipe
     *
     * @param Recipe $recipe
     * @param string|null $layoutSlug Optional layout override
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateRecipePDF(Recipe $recipe, ?string $layoutSlug = null): \Barryvdh\DomPDF\PDF
    {
        // Load relationships
        $recipe->load([
            'recipeIngredients.ingredient.tags',
            'steps',
            'layout',
            'protein'
        ]);

        // Determine layout
        $layout = $this->getLayout($recipe, $layoutSlug);

        // Prepare view data
        $data = [
            'recipe' => $recipe,
            'layout' => $layout,
            'config' => $layout->config,
        ];

        // Get the appropriate view based on layout slug
        $view = "pdf.recipes.layouts.{$layout->slug}";

        // Generate PDF
        $pdf = Pdf::loadView($view, $data);

        // Configure PDF settings
        $pdf->setPaper('letter', 'portrait');

        // Set metadata
        $pdf->setOption('title', $recipe->title);
        $pdf->setOption('subject', 'Recipe');
        if ($recipe->author) {
            $pdf->setOption('author', $recipe->author);
        }

        return $pdf;
    }

    /**
     * Generate PDF for a recipe collection (cookbook)
     *
     * @param RecipeCollection $collection
     * @param string|null $layoutSlug
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateCollectionPDF(RecipeCollection $collection, ?string $layoutSlug = null): \Barryvdh\DomPDF\PDF
    {
        // Load recipes with relationships
        $collection->load([
            'recipes.recipeIngredients.ingredient.tags',
            'recipes.steps',
            'recipes.protein'
        ]);

        // Get layout
        $layout = $layoutSlug
            ? RecipeLayout::where('slug', $layoutSlug)->firstOrFail()
            : RecipeLayout::where('slug', config('recipes.default_layout', 'classic'))->firstOrFail();

        // Prepare view data
        $data = [
            'collection' => $collection,
            'recipes' => $collection->recipes()->orderBy('order')->get(),
            'layout' => $layout,
            'config' => $layout->config,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.recipes.collection', $data);

        // Configure PDF settings
        $pdf->setPaper('letter', 'portrait');
        $pdf->setOption('title', $collection->title);
        $pdf->setOption('subject', 'Cookbook');

        return $pdf;
    }

    /**
     * Download recipe PDF
     *
     * @param Recipe $recipe
     * @param string|null $layoutSlug
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadRecipePDF(Recipe $recipe, ?string $layoutSlug = null)
    {
        $pdf = $this->generateRecipePDF($recipe, $layoutSlug);

        $filename = $this->sanitizeFilename($recipe->title) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Stream recipe PDF (for preview)
     *
     * @param Recipe $recipe
     * @param string|null $layoutSlug
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function streamRecipePDF(Recipe $recipe, ?string $layoutSlug = null)
    {
        $pdf = $this->generateRecipePDF($recipe, $layoutSlug);

        return $pdf->stream();
    }

    /**
     * Save recipe PDF to storage
     *
     * @param Recipe $recipe
     * @param string|null $layoutSlug
     * @return string Path to saved PDF
     */
    public function saveRecipePDF(Recipe $recipe, ?string $layoutSlug = null): string
    {
        $pdf = $this->generateRecipePDF($recipe, $layoutSlug);

        $filename = $this->sanitizeFilename($recipe->title) . '_' . time() . '.pdf';
        $path = config('recipes.pdf_path') . '/' . $filename;

        $disk = config('recipes.pdf_disk', 'local');
        Storage::disk($disk)->put($path, $pdf->output());

        return $path;
    }

    /**
     * Get layout for recipe
     *
     * @param Recipe $recipe
     * @param string|null $layoutSlug Optional override
     * @return RecipeLayout
     */
    protected function getLayout(Recipe $recipe, ?string $layoutSlug = null): RecipeLayout
    {
        // Use override if provided
        if ($layoutSlug) {
            return RecipeLayout::where('slug', $layoutSlug)->firstOrFail();
        }

        // Use recipe's assigned layout
        if ($recipe->layout) {
            return $recipe->layout;
        }

        // Fall back to default
        $defaultSlug = config('recipes.default_layout', 'classic');
        return RecipeLayout::where('slug', $defaultSlug)->firstOrFail();
    }

    /**
     * Sanitize filename for safe filesystem use
     *
     * @param string $filename
     * @return string
     */
    protected function sanitizeFilename(string $filename): string
    {
        // Remove or replace characters not safe for filenames
        $filename = preg_replace('/[^a-z0-9\-\_]/i', '_', $filename);

        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);

        // Trim underscores from ends
        $filename = trim($filename, '_');

        // Limit length
        if (strlen($filename) > 100) {
            $filename = substr($filename, 0, 100);
        }

        return $filename ?: 'recipe';
    }

    /**
     * Get dietary tags as formatted badges
     *
     * @param Recipe $recipe
     * @return string HTML
     */
    public function getDietaryTagsBadgesHTML(Recipe $recipe): string
    {
        $tags = $recipe->dietaryTags();

        if ($tags->isEmpty()) {
            return '';
        }

        $html = '';
        foreach ($tags as $tag) {
            $color = $tag->color ?? '#666';
            $icon = $tag->icon ?? '';

            $html .= sprintf(
                '<span class="dietary-tag" style="background-color: %s; color: white; padding: 4px 8px; border-radius: 4px; margin-right: 8px; font-size: 0.85rem; display: inline-block;">%s %s</span>',
                $color,
                $icon,
                htmlspecialchars($tag->name)
            );
        }

        return $html;
    }
}
