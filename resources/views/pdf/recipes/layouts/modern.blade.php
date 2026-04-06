<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $recipe->title }}</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            padding: 54pt;
        }

        .header {
            margin-bottom: 30px;
        }

        h1 {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 32pt;
            font-weight: 300;
            color: #000;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .metadata {
            display: table;
            width: 100%;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }

        .metadata-item {
            display: table-cell;
            text-align: center;
            font-size: 9pt;
        }

        .metadata-value {
            display: block;
            font-size: 14pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 3px;
        }

        .metadata-label {
            display: block;
            text-transform: uppercase;
            font-size: 8pt;
            color: #999;
            letter-spacing: 0.5px;
        }

        .recipe-image {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
            margin-bottom: 30px;
        }

        .dietary-tags {
            margin: 15px 0;
        }

        .dietary-tag {
            display: inline-block;
            padding: 5px 12px;
            margin-right: 6px;
            margin-bottom: 6px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .description {
            font-size: 11pt;
            color: #666;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        h2 {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 16pt;
            font-weight: bold;
            color: #000;
            margin-top: 35px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-divider {
            height: 3px;
            background: linear-gradient(to right, #000 0%, #ccc 100%);
            margin-bottom: 20px;
        }

        .ingredients-list {
            list-style: none;
            margin-bottom: 35px;
            column-count: 2;
            column-gap: 30px;
        }

        .ingredients-list li {
            padding: 8px 0;
            border-bottom: 1px dotted #e0e0e0;
            page-break-inside: avoid;
        }

        .ingredient-amount {
            display: inline-block;
            min-width: 60px;
            font-weight: 600;
            color: #000;
        }

        .ingredient-name {
            color: #333;
        }

        .ingredient-preparation {
            color: #999;
            font-size: 9pt;
        }

        .steps-list {
            list-style: none;
            counter-reset: step-counter;
            margin-bottom: 35px;
        }

        .steps-list li {
            padding: 15px 0;
            padding-left: 50px;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
            page-break-inside: avoid;
        }

        .steps-list li:last-child {
            border-bottom: none;
        }

        .steps-list li:before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: 0;
            top: 15px;
            width: 35px;
            height: 35px;
            background-color: #000;
            color: white;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            font-size: 11pt;
        }

        .notes {
            margin-top: 35px;
            padding: 20px;
            background-color: #f9f9f9;
            border-left: 4px solid #000;
        }

        .notes h3 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .notes p {
            color: #666;
            font-size: 10pt;
            line-height: 1.6;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 8pt;
            color: #999;
        }

        .attribution {
            margin-bottom: 8px;
        }

        .attribution a {
            color: #666;
            text-decoration: none;
        }

        .branding {
            margin-top: 15px;
            text-align: right;
            color: #ccc;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
    <div class="header">
        <h1>{{ $recipe->title }}</h1>

        @if($recipe->description)
            <div class="description">{{ $recipe->description }}</div>
        @endif

        <div class="metadata">
            @if($recipe->servings)
                <div class="metadata-item">
                    <span class="metadata-value">{{ $recipe->servings }}</span>
                    <span class="metadata-label">Servings</span>
                </div>
            @endif
            @if($recipe->prep_time_minutes)
                <div class="metadata-item">
                    <span class="metadata-value">{{ $recipe->prep_time_minutes }}</span>
                    <span class="metadata-label">Prep (min)</span>
                </div>
            @endif
            @if($recipe->cook_time_minutes)
                <div class="metadata-item">
                    <span class="metadata-value">{{ $recipe->cook_time_minutes }}</span>
                    <span class="metadata-label">Cook (min)</span>
                </div>
            @endif
            @if($recipe->total_time_minutes)
                <div class="metadata-item">
                    <span class="metadata-value">{{ $recipe->total_time_minutes }}</span>
                    <span class="metadata-label">Total (min)</span>
                </div>
            @endif
        </div>

        @php
            $dietaryTags = $recipe->dietaryTags();
        @endphp
        @if($dietaryTags->isNotEmpty())
            <div class="dietary-tags">
                @foreach($dietaryTags as $tag)
                    <span class="dietary-tag" style="background-color: {{ $tag->color ?? '#666' }}; color: white;">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>

    @if($recipe->image_path && Storage::disk(config('recipes.image_disk'))->exists($recipe->image_path))
        <img src="{{ Storage::disk(config('recipes.image_disk'))->url($recipe->image_path) }}"
             alt="{{ $recipe->title }}"
             class="recipe-image">
    @endif

    <h2>Ingredients</h2>
    <div class="section-divider"></div>
    <ul class="ingredients-list">
        @foreach($recipe->recipeIngredients()->orderBy('order')->get() as $ri)
            <li>
                @if($ri->amount || $ri->unit)
                    <span class="ingredient-amount">{{ $ri->amount }} {{ $ri->unit }}</span>
                @endif
                <span class="ingredient-name">{{ $ri->ingredient->name }}</span>
                @if($ri->preparation)
                    <span class="ingredient-preparation">({{ $ri->preparation }})</span>
                @endif
            </li>
        @endforeach
    </ul>

    <h2>Instructions</h2>
    <div class="section-divider"></div>
    <ol class="steps-list">
        @foreach($recipe->steps()->orderBy('step_number')->get() as $step)
            <li>{{ $step->instruction }}</li>
        @endforeach
    </ol>

    @if($recipe->notes)
        <div class="notes">
            <h3>Chef's Notes</h3>
            <p>{{ $recipe->notes }}</p>
        </div>
    @endif

    <div class="footer">
        @if($recipe->source_url || $recipe->source_name)
            <div class="attribution">
                SOURCE:
                @if($recipe->source_name)
                    {{ $recipe->source_name }}
                @endif
                @if($recipe->source_url)
                    • <a href="{{ $recipe->source_url }}">{{ $recipe->source_url }}</a>
                @endif
            </div>
        @endif

        @if($recipe->author)
            <div class="attribution">
                AUTHOR: {{ strtoupper($recipe->author) }}
            </div>
        @endif

        <div class="branding">
            Recipe Manager • {{ now()->format('F j, Y') }}
        </div>
    </div>
    </div>
</body>
</html>
