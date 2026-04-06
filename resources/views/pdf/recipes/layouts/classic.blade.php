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
            font-family: 'Times-Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #2c3e50;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            padding: 54pt;
        }

        .header {
            border-bottom: 3px solid #8b4513;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        h1 {
            font-family: 'Times-Roman', Times, serif;
            font-size: 28pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .metadata {
            display: table;
            width: 100%;
            margin-top: 15px;
            font-size: 10pt;
            color: #666;
        }

        .metadata-item {
            display: table-cell;
            padding-right: 20px;
        }

        .metadata-label {
            font-weight: bold;
            color: #8b4513;
        }

        .recipe-image {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            margin-bottom: 25px;
            border: 1px solid #ddd;
        }

        .dietary-tags {
            margin: 15px 0;
        }

        .dietary-tag {
            display: inline-block;
            padding: 4px 10px;
            margin-right: 8px;
            margin-bottom: 5px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }

        .description {
            font-style: italic;
            color: #555;
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #8b4513;
        }

        h2 {
            font-family: 'Times-Roman', Times, serif;
            font-size: 18pt;
            font-weight: bold;
            color: #8b4513;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 8px;
        }

        .ingredients-list {
            list-style: none;
            margin-bottom: 30px;
        }

        .ingredients-list li {
            padding: 6px 0;
            padding-left: 25px;
            position: relative;
        }

        .ingredients-list li:before {
            content: "";
            position: absolute;
            left: 0;
            width: 10px;
            height: 10px;
            border: 2px solid #8b4513;
            top: 8px;
        }

        .ingredient-amount {
            font-weight: bold;
            color: #2c3e50;
        }

        .ingredient-name {
            color: #2c3e50;
        }

        .ingredient-preparation {
            color: #666;
            font-style: italic;
        }

        .steps-list {
            list-style: none;
            counter-reset: step-counter;
            margin-bottom: 30px;
        }

        .steps-list li {
            padding: 12px 0;
            padding-left: 45px;
            position: relative;
            page-break-inside: avoid;
        }

        .steps-list li:before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: 0;
            width: 30px;
            height: 30px;
            background-color: #8b4513;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            font-family: 'Times-Roman', Times, serif;
        }

        .notes {
            margin-top: 30px;
            padding: 15px;
            background-color: #fffaf0;
            border: 1px solid #f4d03f;
            border-radius: 4px;
        }

        .notes h3 {
            font-family: 'Times-Roman', Times, serif;
            font-size: 14pt;
            font-weight: bold;
            color: #8b4513;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            font-size: 9pt;
            color: #999;
        }

        .attribution {
            margin-bottom: 10px;
        }

        .attribution a {
            color: #8b4513;
            text-decoration: none;
        }

        .branding {
            text-align: center;
            color: #ccc;
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
                    <span class="metadata-label">Servings:</span> {{ $recipe->servings }}
                </div>
            @endif
            @if($recipe->prep_time_minutes)
                <div class="metadata-item">
                    <span class="metadata-label">Prep:</span> {{ $recipe->prep_time_minutes }} min
                </div>
            @endif
            @if($recipe->cook_time_minutes)
                <div class="metadata-item">
                    <span class="metadata-label">Cook:</span> {{ $recipe->cook_time_minutes }} min
                </div>
            @endif
            @if($recipe->total_time_minutes)
                <div class="metadata-item">
                    <span class="metadata-label">Total:</span> {{ $recipe->total_time_minutes }} min
                </div>
            @endif
            @if($recipe->difficulty)
                <div class="metadata-item">
                    <span class="metadata-label">Difficulty:</span> {{ ucfirst($recipe->difficulty) }}
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
    <ul class="ingredients-list">
        @foreach($recipe->recipeIngredients()->orderBy('order')->get() as $ri)
            <li>
                @if($ri->amount || $ri->unit)
                    <span class="ingredient-amount">
                        {{ $ri->amount }} {{ $ri->unit }}
                    </span>
                @endif
                <span class="ingredient-name">{{ $ri->ingredient->name }}</span>
                @if($ri->preparation)
                    <span class="ingredient-preparation">, {{ $ri->preparation }}</span>
                @endif
                @if($ri->is_optional)
                    <span class="ingredient-preparation">(optional)</span>
                @endif
            </li>
        @endforeach
    </ul>

    <h2>Instructions</h2>
    <ol class="steps-list">
        @foreach($recipe->steps()->orderBy('step_number')->get() as $step)
            <li>{{ $step->instruction }}</li>
        @endforeach
    </ol>

    @if($recipe->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{{ $recipe->notes }}</p>
        </div>
    @endif

    <div class="footer">
        @if($recipe->source_url || $recipe->source_name)
            <div class="attribution">
                <strong>Source:</strong>
                @if($recipe->source_name)
                    {{ $recipe->source_name }}
                @endif
                @if($recipe->source_url)
                    <br><a href="{{ $recipe->source_url }}">{{ $recipe->source_url }}</a>
                @endif
            </div>
        @endif

        @if($recipe->author)
            <div class="attribution">
                <strong>Author:</strong> {{ $recipe->author }}
            </div>
        @endif

        <div class="branding">
            Generated with Recipe Manager • {{ now()->format('F j, Y') }}
        </div>
    </div>
    </div>
</body>
</html>
