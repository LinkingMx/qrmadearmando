<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChainResource\Pages;
use App\Models\Chain;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChainResource extends Resource
{
    protected static ?string $model = Chain::class;

    /**
     * Spanish menus and icons.
     */
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Organización';

    protected static ?string $navigationLabel = 'Cadenas';

    protected static ?string $pluralModelLabel = 'Cadenas';

    protected static ?string $modelLabel = 'Cadena';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Cadena')
                    ->description('Define la cadena empresarial que agrupa marcas y sucursales')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Cadena')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('Ej: Cadenas Don Carlos, Grupo Mochomos')
                            ->prefixIcon('heroicon-o-globe-alt')
                            ->helperText('Nombre único que identifica esta cadena empresarial'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cadena')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('brands_count')
                    ->label('Total de Marcas')
                    ->counts('brands')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->hidden(fn ($record) => $record->brands()->exists())
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Cadena eliminada')
                            ->body('La cadena ha sido eliminada exitosamente.')
                            ->icon('heroicon-o-check-circle')
                    ),
            ])
            ->bulkActions([
                // No bulk actions for chains
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChains::route('/'),
            'create' => Pages\CreateChain::route('/create'),
            'edit' => Pages\EditChain::route('/{record}/edit'),
        ];
    }

    public static function canDelete($record): bool
    {
        return $record->brands()->count() === 0;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
