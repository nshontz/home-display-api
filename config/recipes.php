<?php

return [
    // Supported recipe schema formats
    'supported_schemas' => [
        'json-ld',
        'microdata',
        'rdfa',
    ],

    // Image storage
    'image_disk' => env('RECIPES_IMAGE_DISK', 'recipes'),
    'image_path' => 'recipes/images',
    'max_image_size' => 5120, // KB

    // PDF generation
    'pdf_disk' => env('RECIPES_PDF_DISK', 'recipes'),
    'pdf_path' => 'recipes/pdfs',
    'default_layout' => 'classic',

    // Import
    'import_timeout' => 30, // seconds
    'download_images' => true,
    'user_agent' => 'Home Recipe Manager/1.0 (https://yourdomain.com; recipe-import)',
    'respect_robots_txt' => true,

    // Ingredient normalization (minimal - preserves specificity)
    'normalize_ingredients' => true,
    'preserve_ingredient_specificity' => true, // Keeps "yellow onion" vs "red onion" distinct
    'ingredient_misspellings' => [
        'tumeric' => 'turmeric',
        'cinamon' => 'cinnamon',
        'parsely' => 'parsley',
        'cilantroe' => 'cilantro',
        'basalmic' => 'balsamic',
        'mozarella' => 'mozzarella',
        'parmesian' => 'parmesan',
        'parmasean' => 'parmesan',
    ],
];
