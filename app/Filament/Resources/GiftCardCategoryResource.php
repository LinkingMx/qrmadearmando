<?php

namespace App\Filament\Resources;

use App\Enums\GiftCardNature;
use App\Filament\Resources\GiftCardCategoryResource\Pages;
use App\Models\GiftCardCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GiftCardCategoryResource extends Resource
{
    protected static ?string $model = GiftCardCategory::class;

    /**
     * Spanish menus and icons.
     */
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Administración de QR';
    protected static ?string $navigationLabel = 'Categorías de QR';
    protected static ?string $pluralModelLabel = 'Categorías de QR';
    protected static ?string $modelLabel = 'Categoría de QR';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Categoría')
                    ->description('Define las categorías para organizar los QR codes')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Categoría')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ej: Empleados, Relaciones Públicas, Convenios')
                            ->prefixIcon('heroicon-o-tag')
                            ->helperText('Nombre descriptivo que identifica esta categoría de QR'),

                        Forms\Components\TextInput::make('prefix')
                            ->label('Prefijo')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->placeholder('Ej: EMCAD, RPCAD, CON')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->helperText('Prefijo único usado en los códigos legacy (solo letras mayúsculas, máx. 10 caracteres)')
                            ->rules(['regex:/^[A-Z]+$/'])
                            ->validationMessages([
                                'regex' => 'El prefijo solo puede contener letras mayúsculas sin números ni caracteres especiales.',
                            ])
                            ->disabled(fn ($record) => $record && $record->giftCards()->exists()),

                        Forms\Components\Select::make('nature')
                            ->label('Naturaleza')
                            ->required()
                            ->options(GiftCardNature::options())
                            ->placeholder('Seleccionar la naturaleza de la categoría')
                            ->prefixIcon('heroicon-o-cube')
                            ->helperText('Define el tipo de transacción de esta categoría')
                            ->disabled(fn ($record) => $record && $record->giftCards()->exists()),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('prefix')
                    ->label('Prefijo')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nature')
                    ->label('Naturaleza')
                    ->badge()
                    ->color(fn (GiftCardNature $state): string => match ($state) {
                        GiftCardNature::PAYMENT_METHOD => 'success',
                        GiftCardNature::DISCOUNT => 'warning',
                    })
                    ->formatStateUsing(fn (GiftCardNature $state): string => $state->label()),

                Tables\Columns\TextColumn::make('gift_cards_count')
                    ->label('Total de QR')
                    ->counts('giftCards')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nature')
                    ->label('Naturaleza')
                    ->options(GiftCardNature::options())
                    ->placeholder('Todas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->hidden(fn ($record) => $record->giftCards()->exists())
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Categoría eliminada')
                            ->body('La categoría ha sido eliminada exitosamente.')
                            ->icon('heroicon-o-check-circle')
                    ),
            ])
            ->bulkActions([
                // No bulk actions for categories
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiftCardCategories::route('/'),
            'create' => Pages\CreateGiftCardCategory::route('/create'),
            'edit' => Pages\EditGiftCardCategory::route('/{record}/edit'),
        ];
    }

    public static function canDelete($record): bool
    {
        return $record->giftCards()->count() === 0;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
