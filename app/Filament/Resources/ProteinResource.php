<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProteinResource\Pages;
use App\Filament\Resources\ProteinResource\RelationManagers;
use App\Models\Protein;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProteinResource extends Resource
{
    protected static ?string $model = Protein::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('vegetarian')
                    ->required(),
                Forms\Components\ColorPicker::make('color')
                    ->required()
                    ->default('#555555'),
                Forms\Components\Textarea::make('aka')
                    ->label('Aliases (comma-separated)')
                    ->placeholder('alias1, alias2, alias3')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('vegetarian')
                    ->inline(false)
                    ->boolean(),
                Tables\Columns\ColorColumn::make('color')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('vegetarian')
                    ->label('Vegetarian'),
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
            'index' => Pages\ListProteins::route('/'),
            'create' => Pages\CreateProtein::route('/create'),
            'edit' => Pages\EditProtein::route('/{record}/edit'),
        ];
    }
}
