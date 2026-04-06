<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecipeLayoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $layouts = [
            [
                'name' => 'Classic',
                'slug' => 'classic',
                'description' => 'Traditional recipe layout with elegant serif typography',
                'template_path' => 'pdf.recipes.layouts.classic',
                'config' => [
                    'fonts' => [
                        'heading' => 'Georgia, serif',
                        'body' => 'Palatino, "Palatino Linotype", serif',
                        'accent' => 'Georgia, serif'
                    ],
                    'colors' => [
                        'primary' => '#2c3e50',
                        'accent' => '#8b4513',
                        'text' => '#1a1a1a'
                    ],
                    'spacing' => [
                        'page_margin' => '20mm',
                        'section_gap' => '8mm'
                    ],
                    'image' => [
                        'position' => 'top',
                        'max_height' => '120mm'
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'footer'
                    ]
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Modern',
                'slug' => 'modern',
                'description' => 'Clean, contemporary layout with sans-serif fonts',
                'template_path' => 'pdf.recipes.layouts.modern',
                'config' => [
                    'fonts' => [
                        'heading' => 'Helvetica, Arial, sans-serif',
                        'body' => 'Helvetica, Arial, sans-serif',
                        'accent' => '"Helvetica Neue", Helvetica, sans-serif'
                    ],
                    'colors' => [
                        'primary' => '#1a1a1a',
                        'accent' => '#e74c3c',
                        'text' => '#333333'
                    ],
                    'spacing' => [
                        'page_margin' => '15mm',
                        'section_gap' => '6mm'
                    ],
                    'image' => [
                        'position' => 'side',
                        'max_height' => '100mm'
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'header'
                    ]
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Rustic',
                'slug' => 'rustic',
                'description' => 'Warm, farmhouse-style layout with mixed typography',
                'template_path' => 'pdf.recipes.layouts.rustic',
                'config' => [
                    'fonts' => [
                        'heading' => '"Century Schoolbook", Georgia, serif',
                        'body' => 'Garamond, "Times New Roman", serif',
                        'accent' => 'Georgia, serif'
                    ],
                    'colors' => [
                        'primary' => '#3a2920',
                        'accent' => '#d4a574',
                        'text' => '#2c2416'
                    ],
                    'spacing' => [
                        'page_margin' => '25mm',
                        'section_gap' => '10mm'
                    ],
                    'image' => [
                        'position' => 'top',
                        'max_height' => '140mm'
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'footer'
                    ]
                ],
                'is_active' => true,
            ],
        ];

        foreach ($layouts as $layout) {
            \App\Models\RecipeLayout::updateOrCreate(
                ['slug' => $layout['slug']],
                $layout
            );
        }
    }
}
