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
            line-height: 1.7;
            color: #3e2723;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            padding: 54pt;
        }

        .header {
            text-align: center;
            border: 3px double #8b7355;
            padding: 25px;
            margin-bottom: 30px;
            background: #faf8f3;
        }

        h1 {
            font-family: 'Times-Roman', Times, serif;
            font-size: 26pt;
            font-weight: bold;
            color: #5d4037;
            margin-bottom: 15px;
            line-height: 1.3;
            text-transform: lowercase;
            font-variant: small-caps;
        }

        .ornamental-divider {
            text-align: center;
            color: #8b7355;
            font-size: 14pt;
            margin: 10px 0;
        }

        .metadata {
            display: table;
            width: 100%;
            margin-top: 15px;
            font-family: 'Times-Roman', Times, serif;
        }

        .metadata-item {
            display: table-cell;
            text-align: center;
            padding: 8px;
            border-right: 1px solid #d7ccc8;
        }

        .metadata-item:last-child {
            border-right: none;
        }

        .metadata-label {
            display: block;
            font-size: 8pt;
            text-transform: uppercase;
            color: #8b7355;
            letter-spacing: 1px;
            margin-bottom: 3px;
        }

        .metadata-value {
            font-size: 12pt;
            font-weight: bold;
            color: #5d4037;
        }

        .recipe-image {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            margin: 25px 0;
            border: 5px solid #faf8f3;
            box-shadow: 0 0 0 1px #8b7355;
        }

        .dietary-tags {
            text-align: center;
            margin: 15px 0;
        }

        .dietary-tag {
            display: inline-block;
            padding: 5px 12px;
            margin: 0 5px 5px;
            border: 2px solid;
            border-radius: 2px;
            font-size: 9pt;
            font-weight: bold;
            font-family: 'Times-Roman', Times, serif;
        }

        .description {
            font-style: italic;
            color: #6d4c41;
            margin: 25px 0;
            padding: 20px;
            background: #faf8f3;
            border-top: 2px solid #d7ccc8;
            border-bottom: 2px solid #d7ccc8;
            text-align: center;
        }

        h2 {
            font-family: 'Times-Roman', Times, serif;
            font-size: 18pt;
            font-weight: bold;
            color: #5d4037;
            margin-top: 35px;
            margin-bottom: 5px;
            text-align: center;
            text-transform: lowercase;
            font-variant: small-caps;
        }

        h2:after {
            content: "";
            display: block;
            width: 80px;
            height: 2px;
            background: #8b7355;
            margin: 8px auto 15px;
        }

        .ingredients-list {
            list-style: none;
            margin: 20px 0 30px;
            padding: 0 30px;
        }

        .ingredients-list li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            border-bottom: 1px dotted #d7ccc8;
        }

        .ingredients-list li:last-child {
            border-bottom: none;
        }

        .ingredients-list li:before {
            content: "";
            position: absolute;
            left: 0;
            width: 8px;
            height: 8px;
            background: #8b7355;
            transform: rotate(45deg);
            top: 12px;
        }

        .ingredient-amount {
            font-weight: bold;
            color: #5d4037;
            font-family: 'Times-Roman', Times, serif;
        }

        .ingredient-name {
            color: #3e2723;
        }

        .ingredient-preparation {
            color: #8d6e63;
            font-style: italic;
            font-size: 10pt;
        }

        .steps-list {
            list-style: none;
            counter-reset: step-counter;
            margin: 20px 0 30px;
            padding: 0 20px;
        }

        .steps-list li {
            padding: 15px 0 15px 60px;
            position: relative;
            page-break-inside: avoid;
        }

        .steps-list li:before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: 0;
            top: 12px;
            width: 40px;
            height: 40px;
            border: 3px solid #8b7355;
            background: #faf8f3;
            color: #5d4037;
            text-align: center;
            line-height: 34px;
            font-weight: bold;
            font-size: 14pt;
            font-family: 'Times-Roman', Times, serif;
        }

        .notes {
            margin-top: 35px;
            padding: 20px;
            background: #fff9e6;
            border: 2px dashed #d4a574;
        }

        .notes h3 {
            font-family: 'Times-Roman', Times, serif;
            font-size: 14pt;
            font-weight: bold;
            color: #5d4037;
            margin-bottom: 12px;
            text-align: center;
            text-transform: lowercase;
            font-variant: small-caps;
        }

        .notes p {
            color: #6d4c41;
            font-style: italic;
            line-height: 1.8;
        }

        .footer {
            margin-top: 45px;
            padding-top: 20px;
            border-top: 3px double #8b7355;
            font-size: 9pt;
            color: #8d6e63;
            text-align: center;
        }

        .attribution {
            margin: 8px 0;
            font-family: 'Times-Roman', Times, serif;
        }

        .attribution strong {
            color: #5d4037;
        }

        .attribution a {
            color: #8b7355;
            text-decoration: none;
        }

        .branding {
            margin-top: 20px;
            color: #bcaaa4;
            font-size: 8pt;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
    <div class="header">
        <h1>{{ $recipe->title }}</h1>

        @if($recipe->description)
            <div class="ornamental-divider">~</div>
            <div class="description">{{ $recipe->description }}</div>
        @endif

        @if($recipe->servings || $recipe->prep_time_minutes || $recipe->cook_time_minutes || $recipe->total_time_minutes)
            <div class="ornamental-divider">~</div>
            <div class="metadata">
                @if($recipe->servings)
                    <div class="metadata-item">
                        <span class="metadata-label">Servings</span>
                        <span class="metadata-value">{{ $recipe->servings }}</span>
                    </div>
                @endif
                @if($recipe->prep_time_minutes)
                    <div class="metadata-item">
                        <span class="metadata-label">Prep Time</span>
                        <span class="metadata-value">{{ $recipe->prep_time_minutes }} min</span>
                    </div>
                @endif
                @if($recipe->cook_time_minutes)
                    <div class="metadata-item">
                        <span class="metadata-label">Cook Time</span>
                        <span class="metadata-value">{{ $recipe->cook_time_minutes }} min</span>
                    </div>
                @endif
                @if($recipe->total_time_minutes)
                    <div class="metadata-item">
                        <span class="metadata-label">Total Time</span>
                        <span class="metadata-value">{{ $recipe->total_time_minutes }} min</span>
                    </div>
                @endif
            </div>
        @endif

        @php
            $dietaryTags = $recipe->dietaryTags();
        @endphp
        @if($dietaryTags->isNotEmpty())
            <div class="ornamental-divider">~</div>
            <div class="dietary-tags">
                @foreach($dietaryTags as $tag)
                    <span class="dietary-tag" style="border-color: {{ $tag->color ?? '#8b7355' }}; color: {{ $tag->color ?? '#8b7355' }};">
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
                    <span class="ingredient-amount">{{ $ri->amount }} {{ $ri->unit }}</span>
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

    <h2>Preparation</h2>
    <ol class="steps-list">
        @foreach($recipe->steps()->orderBy('step_number')->get() as $step)
            <li>{{ $step->instruction }}</li>
        @endforeach
    </ol>

    @if($recipe->notes)
        <div class="notes">
            <h3>Kitchen Notes</h3>
            <p>{{ $recipe->notes }}</p>
        </div>
    @endif

    <div class="footer">
        @if($recipe->source_url || $recipe->source_name || $recipe->author)
            <div class="ornamental-divider">~ ~ ~</div>
        @endif

        @if($recipe->author)
            <div class="attribution">
                <strong>Recipe by:</strong> {{ $recipe->author }}
            </div>
        @endif

        @if($recipe->source_url || $recipe->source_name)
            <div class="attribution">
                <strong>Adapted from:</strong>
                @if($recipe->source_name)
                    {{ $recipe->source_name }}
                @endif
                @if($recipe->source_url)
                    <br><a href="{{ $recipe->source_url }}">{{ $recipe->source_url }}</a>
                @endif
            </div>
        @endif

        <div class="branding">
            From the kitchen • {{ now()->format('F j, Y') }}
        </div>
    </div>
    </div>
</body>
</html>
