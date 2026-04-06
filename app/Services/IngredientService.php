<?php

namespace App\Services;

use App\Models\Ingredient;
use Illuminate\Support\Str;

class IngredientService
{
    /**
     * Common measurement units (full words, abbreviations, and variations)
     */
    protected array $units = [
        // Volume
        'cup', 'cups', 'c',
        'tablespoon', 'tablespoons', 'tbsp', 'tbs', 'T',
        'teaspoon', 'teaspoons', 'tsp', 'ts', 't',
        'fluid ounce', 'fluid ounces', 'fl oz', 'fl. oz.',
        'milliliter', 'milliliters', 'millilitre', 'millilitres', 'ml', 'mL',
        'liter', 'liters', 'litre', 'litres', 'l', 'L',
        'pint', 'pints', 'pt',
        'quart', 'quarts', 'qt',
        'gallon', 'gallons', 'gal',

        // Weight
        'pound', 'pounds', 'lb', 'lbs', 'lb.',
        'ounce', 'ounces', 'oz', 'oz.',
        'gram', 'grams', 'gramme', 'grammes', 'g',
        'kilogram', 'kilograms', 'kg',

        // Count/Size
        'piece', 'pieces', 'pc',
        'package', 'packages', 'pkg',
        'can', 'cans',
        'jar', 'jars',
        'box', 'boxes',
        'bag', 'bags',
        'bunch', 'bunches',
        'sprig', 'sprigs',
        'head', 'heads',
        'clove', 'cloves',
        'stalk', 'stalks',
        'slice', 'slices',
        'strip', 'strips',
        'block', 'blocks',

        // Approximations
        'pinch', 'pinches',
        'dash', 'dashes',
        'handful', 'handfuls',
    ];

    /**
     * Size descriptors that should stay with the ingredient name
     */
    protected array $sizeDescriptors = [
        'small', 'medium', 'large', 'extra-large', 'extra large',
        'whole', 'half', 'quarter',
        'baby', 'jumbo',
        'thick-cut', 'thin-cut',
        'bone-in', 'boneless',
        'skin-on', 'skinless',
    ];

    /**
     * Preparation words to extract
     */
    protected array $preparationWords = [
        'diced', 'chopped', 'minced', 'sliced', 'crushed', 'grated', 'shredded',
        'julienned', 'rough chopped', 'roughly chopped', 'finely chopped', 'coarsely chopped',
        'thinly sliced', 'thickly sliced', 'halved', 'quartered',
        'beaten', 'whisked', 'melted', 'softened', 'toasted', 'roasted',
        'peeled', 'seeded', 'deveined', 'trimmed', 'stemmed',
        'fresh', 'frozen', 'dried', 'canned', 'cooked', 'raw',
        'drained', 'rinsed',
    ];

    /**
     * Adjectives/modifiers that should be removed from the beginning
     */
    protected array $leadingAdjectives = [
        'roughly', 'finely', 'coarsely', 'thinly', 'thickly',
        'about', 'approximately',
    ];

    /**
     * Words that indicate an invalid/incomplete ingredient
     */
    protected array $invalidFragments = [
        'boneless', 'bone-in', 'skin-on', 'skinless', // Just a descriptor
        'cooked', 'fresh', 'frozen', 'dried', 'canned', 'raw', // Just a state
        'of choice', 'to taste', // Incomplete phrases
        '()', '( )', // Empty parentheses
    ];

    /**
     * Parse an ingredient string into structured components
     *
     * @param string $rawIngredient The raw ingredient string from recipe
     * @return array|null Parsed components or null if invalid
     */
    public function parseIngredient(string $rawIngredient): ?array
    {
        $original = $rawIngredient;
        $ingredient = trim($rawIngredient);

        if (empty($ingredient)) {
            return null;
        }

        // Check for invalid fragments
        if ($this->isInvalidFragment($ingredient)) {
            return null;
        }

        $parsed = [
            'name' => null,
            'amount' => null,
            'unit' => null,
            'preparation' => null,
            'notes' => null,
            'original' => $original,
        ];

        // Split on comma - often separates notes/prep
        $parts = array_map('trim', explode(',', $ingredient, 2));
        $mainPart = $parts[0];
        $notes = $parts[1] ?? null;

        // Handle HTML entities
        $mainPart = html_entity_decode($mainPart, ENT_QUOTES | ENT_HTML5);

        // Remove leading adjectives like "roughly"
        $mainPart = $this->removeLeadingAdjectives($mainPart);

        // Extract and simplify amount (handles ranges, compound units)
        $amountData = $this->extractAmount($mainPart);
        $parsed['amount'] = $amountData['amount'];
        $mainPart = $amountData['remainder'];

        // Extract unit
        $unitData = $this->extractUnit($mainPart);
        $parsed['unit'] = $unitData['unit'];
        $mainPart = $unitData['remainder'];

        // Handle special structures like "zest of 1 lime" → "lime zest"
        $mainPart = $this->handleSpecialStructures($mainPart);

        // Extract preparation words
        $prepData = $this->extractPreparation($mainPart);
        $parsed['preparation'] = $prepData['preparation'];
        $mainPart = $prepData['remainder'];

        // Clean up the ingredient name
        $mainPart = $this->cleanIngredientName($mainPart);

        // Final validation
        if (empty($mainPart) || $this->isInvalidFragment($mainPart)) {
            return null;
        }

        $parsed['name'] = $mainPart;
        $parsed['notes'] = $notes;

        return $parsed;
    }

    /**
     * Find or create an ingredient by name
     *
     * @param string $name Ingredient name
     * @return Ingredient
     */
    public function findOrCreateIngredient(string $name): Ingredient
    {
        // Try exact match first
        $ingredient = Ingredient::where('name', $name)->first();
        if ($ingredient) {
            return $ingredient;
        }

        // Try case-insensitive
        $ingredient = Ingredient::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($ingredient) {
            return $ingredient;
        }

        // Try singular/plural variants
        $singular = $this->singularize($name);
        $plural = Str::plural($name);

        foreach ([$singular, $plural] as $variant) {
            if ($variant !== $name) {
                $ingredient = Ingredient::whereRaw('LOWER(name) = ?', [strtolower($variant)])->first();
                if ($ingredient) {
                    return $ingredient;
                }
            }
        }

        // Create new ingredient
        return Ingredient::create([
            'name' => $name,
        ]);
    }

    /**
     * Extract amount from string, handling ranges and compound units
     * Converts ranges to the upper bound (3-4 becomes 4)
     */
    protected function extractAmount(string $text): array
    {
        $amount = null;
        $remainder = $text;

        // Pattern matches:
        // - Fractions: 1/2, 3/4
        // - Decimals: 1.5, 0.25
        // - Whole numbers: 1, 2, 10
        // - Ranges: 3-4, 3 to 4, 3 or 4
        // - Compound: 1 1/2 (one and a half)
        $pattern = '/^([\d]+[\s]?[\d\/\.]*(?:\s*(?:-|to|or)\s*[\d]+[\s]?[\d\/\.]*)?)(?=\s|$)/i';

        if (preg_match($pattern, trim($remainder), $matches)) {
            $amountStr = trim($matches[1]);

            // Handle ranges - take the upper bound
            if (preg_match('/([\d\s\/\.]+)(?:\s*(?:-|to|or)\s*)([\d\s\/\.]+)/i', $amountStr, $rangeMatches)) {
                $amountStr = trim($rangeMatches[2]); // Take upper bound
            }

            $amount = $amountStr;
            $remainder = trim(substr($remainder, strlen($matches[0])));
        }

        return [
            'amount' => $amount,
            'remainder' => $remainder,
        ];
    }

    /**
     * Extract unit from string, handling compound units like "cup/8 ounces"
     */
    protected function extractUnit(string $text): array
    {
        $unit = null;
        $remainder = $text;

        // Build pattern from units array
        $unitsPattern = implode('|', array_map('preg_quote', $this->units));

        // Match unit at start, handling compound like "cup/8 ounces"
        $pattern = '/^(' . $unitsPattern . ')(?:\s*\/\s*[\d]+\s*(?:' . $unitsPattern . '))?(?=\s|$)/i';

        if (preg_match($pattern, trim($remainder), $matches)) {
            $unitStr = trim($matches[1]);

            // Simplify compound units - just take the first unit
            if (strpos($unitStr, '/') !== false) {
                $unitStr = trim(explode('/', $unitStr)[0]);
            }

            $unit = $unitStr;
            $remainder = trim(substr($remainder, strlen($matches[0])));
        }

        return [
            'unit' => $unit,
            'remainder' => $remainder,
        ];
    }

    /**
     * Extract preparation words from string
     */
    protected function extractPreparation(string $text): array
    {
        $preparation = [];
        $remainder = $text;

        // Sort by length descending to match longer phrases first
        $prepWords = $this->preparationWords;
        usort($prepWords, fn($a, $b) => strlen($b) - strlen($a));

        foreach ($prepWords as $prep) {
            if (stripos($remainder, $prep) !== false) {
                $preparation[] = $prep;
                // Remove prep word from text
                $remainder = trim(preg_replace('/\b' . preg_quote($prep, '/') . '\b/i', '', $remainder));
            }
        }

        return [
            'preparation' => !empty($preparation) ? implode(', ', $preparation) : null,
            'remainder' => $remainder,
        ];
    }

    /**
     * Handle special ingredient structures
     * E.g., "zest of 1 lime" → "1 lime, zest"
     * E.g., "juice of 1 lemon" → "1 lemon, juice"
     */
    protected function handleSpecialStructures(string $text): string
    {
        // Pattern: "X of Y" where X is zest/juice/peel
        $pattern = '/^(zest|juice|peel|rind)\s+of\s+(.+)$/i';

        if (preg_match($pattern, $text, $matches)) {
            $part = trim($matches[1]); // zest, juice, etc
            $ingredient = trim($matches[2]); // 1 lime

            // Remove any number from ingredient (it's the amount, not part of name)
            $ingredient = preg_replace('/^[\d\s\/\.]+/', '', $ingredient);
            $ingredient = trim($ingredient);

            return $ingredient . ' ' . $part;
        }

        return $text;
    }

    /**
     * Remove leading adjectives/modifiers
     */
    protected function removeLeadingAdjectives(string $text): string
    {
        foreach ($this->leadingAdjectives as $adjective) {
            if (stripos($text, $adjective . ' ') === 0) {
                $text = trim(substr($text, strlen($adjective)));
            }
        }

        return $text;
    }

    /**
     * Clean ingredient name
     */
    protected function cleanIngredientName(string $name): string
    {
        // Remove empty parentheses
        $name = preg_replace('/\(\s*\)/', '', $name);

        // Remove parenthetical notes
        $name = preg_replace('/\([^)]+\)/', '', $name);

        // Remove extra whitespace
        $name = preg_replace('/\s+/', ' ', $name);

        // Clean up leading/trailing punctuation
        $name = trim($name, " \t\n\r\0\x0B,.-");

        return $name;
    }

    /**
     * Check if a string is an invalid ingredient fragment
     */
    protected function isInvalidFragment(string $text): bool
    {
        $text = strtolower(trim($text));

        // Too short
        if (strlen($text) < 2) {
            return true;
        }

        // Is it just a fragment?
        foreach ($this->invalidFragments as $fragment) {
            if ($text === strtolower($fragment)) {
                return true;
            }
        }

        // Is it just punctuation?
        if (preg_match('/^[\(\)\[\]\{\},\.;:\-\s]+$/', $text)) {
            return true;
        }

        return false;
    }

    /**
     * Simple singularization for matching
     */
    protected function singularize(string $name): string
    {
        // Use Laravel's Str::singular() helper
        return Str::singular($name);
    }

    /**
     * Normalize ingredient name for classification/matching
     * (This is for dietary tag classification, not storage)
     */
    public function normalizeForClassification(string $name): string
    {
        $name = strtolower(trim($name));

        // Remove size descriptors for classification purposes
        foreach ($this->sizeDescriptors as $descriptor) {
            $name = preg_replace('/\b' . preg_quote($descriptor, '/') . '\b/i', '', $name);
        }

        // Remove adjectives
        $adjectives = ['fresh', 'frozen', 'dried', 'canned', 'whole', 'ground', 'crushed'];
        foreach ($adjectives as $adj) {
            $name = preg_replace('/\b' . preg_quote($adj, '/') . '\b/i', '', $name);
        }

        // Clean up spaces
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);

        return $name;
    }
}
