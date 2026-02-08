<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    /**
     * Spanish menus and icons.
     */
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Organización';

    protected static ?string $navigationLabel = 'Marcas';

    protected static ?string $pluralModelLabel = 'Marcas';

    protected static ?string $modelLabel = 'Marca';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Marca')
                    ->description('Define la marca que agrupa sucursales dentro de una cadena')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\Select::make('chain_id')
                            ->label('Cadena')
                            ->relationship('chain', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccionar cadena')
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->helperText('Cadena a la que pertenece esta marca'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Marca')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Mochomos, Don Carlos, La Vaca')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->helperText('Nombre de la marca (debe ser único dentro de la cadena)'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('chain.name')
                    ->label('Cadena')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('branches_count')
                    ->label('Sucursales')
                    ->counts('branches')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('gift_cards_count')
                    ->label('QR Codes')
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
                Tables\Filters\SelectFilter::make('chain_id')
                    ->label('Cadena')
                    ->relationship('chain', 'name')
                    ->placeholder('Todas las cadenas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->hidden(fn ($record) => $record->branches()->exists() || $record->giftCards()->exists())
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Marca eliminada')
                            ->body('La marca ha sido eliminada exitosamente.')
                            ->icon('heroicon-o-check-circle')
                    ),
            ])
            ->bulkActions([
                // No bulk actions for brands
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }

    public static function canDelete($record): bool
    {
        return $record->branches()->count() === 0 && $record->giftCards()->count() === 0;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
