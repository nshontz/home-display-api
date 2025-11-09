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
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DinnerResource extends Resource
{
    protected static ?string $model = Dinner::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uid')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('complete'),
                Forms\Components\DateTimePicker::make('date'),
                Forms\Components\Textarea::make('event')
                    ->label('Event Data (JSON)')
                    ->rows(3)
                    ->placeholder('Event JSON data'),
                Forms\Components\Select::make('protein_id')
                    ->relationship('protein', 'name'),
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
                    ->searchable()
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('protein')
                    ->relationship('protein', 'name'),
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
