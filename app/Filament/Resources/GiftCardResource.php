<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftCardResource\Pages;
use App\Filament\Resources\GiftCardResource\RelationManagers;
use App\Models\GiftCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class GiftCardResource extends Resource
{
    protected static ?string $model = GiftCard::class;

    /**
     * Spanish menus and icons.
     */
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationGroup = 'Administración de QR';
    protected static ?string $navigationLabel = 'QR Empleados';
    protected static ?string $pluralModelLabel = 'QR Empleados';
    protected static ?string $modelLabel = 'QR Empleado';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del QR Empleado')
                    ->icon('heroicon-o-qr-code')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('UUID')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-identification')
                            ->visible(fn ($record) => $record !== null)
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('legacy_id')
                                    ->label('ID Legacy')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: EMCAD20005')
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->maxLength(255),
                                Forms\Components\Select::make('user_id')
                                    ->label('Usuario Asignado')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccionar usuario')
                                    ->prefixIcon('heroicon-m-user'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('status')
                                    ->label('Estado')
                                    ->helperText('Activo/Inactivo')
                                    ->default(true),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Fecha de Expiración')
                                    ->placeholder('Seleccionar fecha')
                                    ->prefixIcon('heroicon-m-calendar-days'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('legacy_id')
                    ->label('ID Legacy')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin asignar'),
                Tables\Columns\IconColumn::make('status')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Fecha de Expiración')
                    ->date()
                    ->sortable()
                    ->placeholder('Sin fecha'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->successNotification(
                        fn (GiftCard $record) => Notification::make()
                            ->success()
                            ->title('QR Empleado eliminado exitosamente')
                            ->body("El QR Empleado '{$record->legacy_id}' ha sido eliminado correctamente.")
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
                                ->title('QR Empleados eliminados exitosamente')
                                ->body('Los QR Empleados seleccionados han sido eliminados correctamente.')
                                ->icon('heroicon-o-check-circle')
                        ),
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
            'index' => Pages\ListGiftCards::route('/'),
            'create' => Pages\CreateGiftCard::route('/create'),
            'edit' => Pages\EditGiftCard::route('/{record}/edit'),
        ];
    }
}
