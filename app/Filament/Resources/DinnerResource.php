<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DinnerResource\Pages;
use App\Filament\Resources\DinnerResource\RelationManagers;
use App\Models\Dinner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DinnerResource extends Resource
{
    protected static ?string $model = Dinner::class;

    protected static ?string $navigationGroup = 'Dinners';
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('open_url')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->url(fn ($record) => $record?->recipe_url)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => !empty($record?->recipe_url))
                    )
                    ->maxLength(255),
                Forms\Components\Select::make('protein_id')
                    ->relationship('protein', 'name'),
                Forms\Components\DateTimePicker::make('complete'),
                Forms\Components\DateTimePicker::make('date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('protein.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipe_url')
                    ->label('Recipe')
                    ->url(fn ($record) => $record->recipe_url)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->placeholder('No recipe')
                    ->toggleable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('protein')
                    ->relationship('protein', 'name'),
                Tables\Filters\Filter::make('missing_protein')
                    ->label('Missing Protein')
                    ->query(fn(Builder $query): Builder => $query->whereNull('protein_id'))
                    ->toggle(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDinners::route('/'),
            'create' => Pages\CreateDinner::route('/create'),
            'edit' => Pages\EditDinner::route('/{record}/edit'),
        ];
    }
}
