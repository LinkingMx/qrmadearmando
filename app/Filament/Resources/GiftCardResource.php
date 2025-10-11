<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftCardResource\Pages;
use App\Filament\Resources\GiftCardResource\RelationManagers;
use App\Models\GiftCard;
use App\Services\TransactionService;
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
    protected static ?int $navigationSort = 1;

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
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: EMCAD20005')
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->helperText('Se generará automáticamente si se deja vacío (formato: EMCAD000001)')
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
                        Forms\Components\Section::make('Códigos QR Generados')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                Forms\Components\View::make('filament.qr-codes')
                                    ->visible(fn ($record) => $record !== null && $record->qr_image_path)
                                    ->viewData(fn ($record) => [
                                        'qrUrls' => $record ? $record->getQrCodeUrls() : ['uuid' => null, 'legacy' => null],
                                        'legacyId' => $record?->legacy_id,
                                        'uuid' => $record?->id,
                                    ]),
                            ])
                            ->visible(fn ($record) => $record !== null),
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
                    ->badge()
                    ->color('primary')
                    ->placeholder('Sin asignar'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('MXN')
                    ->sortable()
                    ->default(0),
                Tables\Columns\IconColumn::make('status')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Fecha de Expiración')
                    ->date()
                    ->sortable()
                    ->placeholder('Sin fecha')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ViewColumn::make('qr_codes')
                    ->label('Códigos QR')
                    ->view('filament.table-qr-codes')
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activos',
                        '0' => 'Inactivos',
                    ])
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),
            ])
            ->bulkActions([
                // No bulk actions - QR Empleados can only be activated/deactivated
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
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

    // Removed soft delete query scope - QR Empleados cannot be deleted

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }
}
