<?php

namespace App\Filament\Resources;

use App\Enums\GiftCardScope;
use App\Filament\Resources\GiftCardResource\Pages;
use App\Filament\Resources\GiftCardResource\RelationManagers;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GiftCardResource extends Resource
{
    protected static ?string $model = GiftCard::class;

    /**
     * Spanish menus and icons.
     */
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Administración de QR';

    protected static ?string $navigationLabel = 'QR Codes';

    protected static ?string $pluralModelLabel = 'QR Codes';

    protected static ?string $modelLabel = 'QR Code';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del QR Code')
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
                                Forms\Components\Select::make('gift_card_category_id')
                                    ->label('Categoría')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccionar categoría')
                                    ->prefixIcon('heroicon-m-tag')
                                    ->live()
                                    ->helperText('El código legacy se generará automáticamente basado en el prefijo de la categoría')
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $set('legacy_id', null);
                                        }
                                    }),
                                Forms\Components\TextInput::make('legacy_id')
                                    ->label('ID Legacy')
                                    ->unique(ignoreRecord: true)
                                    ->placeholder(function (Forms\Get $get) {
                                        $categoryId = $get('gift_card_category_id');
                                        if (! $categoryId) {
                                            return 'Primero selecciona una categoría';
                                        }
                                        $category = GiftCardCategory::find($categoryId);

                                        return $category
                                            ? 'Ej: '.$category->prefix.'000001 (automático)'
                                            : 'Primero selecciona una categoría';
                                    })
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->helperText('Se generará automáticamente si se deja vacío')
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Usuario Asignado')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccionar usuario')
                                    ->prefixIcon('heroicon-m-user'),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Fecha de Expiración')
                                    ->placeholder('Seleccionar fecha')
                                    ->prefixIcon('heroicon-m-calendar-days'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('status')
                                    ->label('Estado')
                                    ->helperText('Activo/Inactivo')
                                    ->default(true),
                            ]),
                    ]),

                Forms\Components\Section::make('Alcance de Uso')
                    ->description('Define dónde puede usarse este QR Code')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Select::make('scope')
                            ->label('Tipo de Alcance')
                            ->options(GiftCardScope::options())
                            ->required()
                            ->default('chain')
                            ->live()
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->helperText('Define el alcance geográfico del QR'),

                        Forms\Components\Select::make('chain_id')
                            ->label('Cadena')
                            ->relationship('chain', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccionar cadena')
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->visible(fn (Forms\Get $get) => $get('scope') === 'chain')
                            ->required(fn (Forms\Get $get) => $get('scope') === 'chain')
                            ->helperText('El QR podrá usarse en todas las sucursales de esta cadena'),

                        Forms\Components\Select::make('brand_id')
                            ->label('Marca')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccionar marca')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->visible(fn (Forms\Get $get) => $get('scope') === 'brand')
                            ->required(fn (Forms\Get $get) => $get('scope') === 'brand')
                            ->helperText('El QR podrá usarse en todas las sucursales de esta marca'),

                        Forms\Components\Select::make('branches')
                            ->label('Sucursales')
                            ->relationship('branches', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccionar sucursales')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->visible(fn (Forms\Get $get) => $get('scope') === 'branch')
                            ->required(fn (Forms\Get $get) => $get('scope') === 'branch')
                            ->helperText('Selecciona una o varias sucursales donde podrá usarse el QR'),
                    ])
                    ->columns(1),

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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('scope')
                    ->label('Alcance')
                    ->badge()
                    ->color(fn (GiftCardScope $state): string => match ($state) {
                        GiftCardScope::CHAIN => 'success',
                        GiftCardScope::BRAND => 'warning',
                        GiftCardScope::BRANCH => 'primary',
                    })
                    ->formatStateUsing(fn (GiftCardScope $state): string => match ($state) {
                        GiftCardScope::CHAIN => 'Cadena',
                        GiftCardScope::BRAND => 'Marca',
                        GiftCardScope::BRANCH => 'Sucursal',
                    }),
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
                Tables\Filters\SelectFilter::make('gift_card_category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->placeholder('Todas las categorías'),
                Tables\Filters\SelectFilter::make('scope')
                    ->label('Alcance')
                    ->options(GiftCardScope::options())
                    ->placeholder('Todos los alcances'),
                Tables\Filters\SelectFilter::make('chain_id')
                    ->label('Cadena')
                    ->relationship('chain', 'name')
                    ->placeholder('Todas las cadenas'),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->placeholder('Todas las marcas'),
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
                // No bulk actions
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
