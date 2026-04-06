<?php

namespace App\Filament\Resources\RecipeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecipeIngredientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipeIngredients';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ingredient_id')
                    ->relationship('ingredient', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('plural_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('category')
                            ->maxLength(255),
                    ])
                    ->label('Ingredient')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->placeholder('1, 1/2, 2 1/4'),
                Forms\Components\TextInput::make('unit')
                    ->label('Unit')
                    ->placeholder('cup, tablespoon, medium'),
                Forms\Components\TextInput::make('preparation')
                    ->label('Preparation')
                    ->placeholder('diced, chopped, minced')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('notes')
                    ->label('Notes')
                    ->placeholder('or substitute with...')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_optional')
                    ->label('Optional ingredient'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ingredient.name')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ingredient.name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('preparation')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_optional')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data, RelationManager $livewire): array {
                        $maxOrder = $livewire->getOwnerRecord()
                            ->recipeIngredients()
                            ->max('order') ?? -1;
                        $data['order'] = $maxOrder + 1;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
