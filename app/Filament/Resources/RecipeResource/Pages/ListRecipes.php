<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Services\RecipeFactory;
use App\Services\RecipeImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListRecipes extends ListRecords
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importFromUrl')
                ->label('Import from URL')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('url')
                        ->label('Recipe URL')
                        ->url()
                        ->required()
                        ->helperText('Paste a recipe URL from NYT Cooking, Serious Eats, AllRecipes, etc.')
                        ->placeholder('https://cooking.nytimes.com/recipes/...')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    try {
                        $importService = new RecipeImportService();
                        $recipeData = $importService->importFromUrl($data['url']);

                        $recipe = RecipeFactory::create($recipeData);

                        Notification::make()
                            ->success()
                            ->title('Recipe imported successfully!')
                            ->body("'{$recipe->title}' has been imported with {$recipe->recipeIngredients->count()} ingredients.")
                            ->send();

                        $this->redirect(RecipeResource::getUrl('edit', ['record' => $recipe]));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Import failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->modalWidth('xl'),

            Actions\CreateAction::make(),
        ];
    }
}
