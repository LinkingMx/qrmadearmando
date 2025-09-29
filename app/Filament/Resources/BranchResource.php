<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    /**
     * Spanish menus and icons.
     */
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Administración de sistema';
    protected static ?string $navigationLabel = 'Sucursales';
    protected static ?string $pluralModelLabel = 'Sucursales';
    protected static ?string $modelLabel = 'Sucursal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Sucursal')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-building-office-2'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->successNotification(
                        fn (Branch $record) => Notification::make()
                            ->success()
                            ->title('Sucursal actualizada exitosamente')
                            ->body("La sucursal '{$record->name}' ha sido actualizada correctamente.")
                            ->icon('heroicon-o-check-circle')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->successNotification(
                        fn (Branch $record) => Notification::make()
                            ->success()
                            ->title('Sucursal eliminada exitosamente')
                            ->body("La sucursal '{$record->name}' ha sido eliminada correctamente.")
                            ->icon('heroicon-o-check-circle')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->successNotification(
                            fn ($records) => Notification::make()
                                ->success()
                                ->title('Sucursales eliminadas exitosamente')
                                ->body('Las sucursales seleccionadas han sido eliminadas correctamente.')
                                ->icon('heroicon-o-check-circle')
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
