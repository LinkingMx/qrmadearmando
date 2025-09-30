<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transacciones';

    protected static ?string $modelLabel = 'Transacción';

    protected static ?string $pluralModelLabel = 'Transacciones';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('gift_card_id')
                    ->relationship('giftCard', 'legacy_id')
                    ->label('Tarjeta de Regalo')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $giftCard = \App\Models\GiftCard::find($state);
                            $set('current_balance', $giftCard?->balance ?? 0);
                        }
                    })
                    ->disabled(fn ($context) => $context === 'edit'),
                Forms\Components\TextInput::make('current_balance')
                    ->label('Saldo Actual')
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($context) => $context === 'create'),
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'credit' => 'Carga',
                        'debit' => 'Descuento',
                        'adjustment' => 'Ajuste Manual',
                    ])
                    ->required()
                    ->reactive()
                    ->disabled(fn ($context) => $context === 'edit')
                    ->visible(fn ($context) => $context === 'create'),
                Forms\Components\TextInput::make('amount')
                    ->label(fn (Forms\Get $get) => match($get('type')) {
                        'credit' => 'Monto a Cargar',
                        'debit' => 'Monto a Descontar',
                        'adjustment' => 'Monto del Ajuste (+ o -)',
                        default => 'Monto',
                    })
                    ->helperText(fn (Forms\Get $get) => $get('type') === 'adjustment'
                        ? 'Use números positivos para aumentar y negativos para disminuir'
                        : null)
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->minValue(fn (Forms\Get $get) => $get('type') === 'adjustment' ? null : 0.01)
                    ->step(0.01)
                    ->reactive()
                    ->disabled(fn ($context) => $context === 'edit')
                    ->visible(fn ($context) => $context === 'create'),
                Forms\Components\Select::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required(fn (Forms\Get $get) =>
                        $get('type') === 'debit' ||
                        ($get('type') === 'adjustment' && $get('amount') < 0)
                    )
                    ->visible(fn (Forms\Get $get, $context) =>
                        $context === 'create' && (
                            $get('type') === 'debit' ||
                            ($get('type') === 'adjustment' && $get('amount') < 0)
                        )
                    )
                    ->helperText('Requerida para descuentos y ajustes que reducen saldo'),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->required(fn (Forms\Get $get) => $get('type') === 'adjustment')
                    ->columnSpanFull()
                    ->rows(3)
                    ->maxLength(500)
                    ->visible(fn ($context) => $context === 'create'),
                // View only fields
                Forms\Components\TextInput::make('balance_before')
                    ->label('Saldo Anterior')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->visible(fn ($context) => $context !== 'create'),
                Forms\Components\TextInput::make('balance_after')
                    ->label('Saldo Nuevo')
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->visible(fn ($context) => $context !== 'create'),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull()
                    ->rows(3)
                    ->disabled()
                    ->visible(fn ($context) => $context !== 'create'),
                Forms\Components\Select::make('admin_user_id')
                    ->relationship('admin', 'name')
                    ->label('Usuario Admin')
                    ->disabled()
                    ->visible(fn ($context) => $context !== 'create'),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Sucursal')
                    ->disabled()
                    ->visible(fn ($context) => $context !== 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('giftCard.legacy_id')
                    ->label('ID Tarjeta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('giftCard.user.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credit' => 'success',
                        'debit' => 'danger',
                        'adjustment' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit' => 'Carga',
                        'debit' => 'Descuento',
                        'adjustment' => 'Ajuste',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Saldo Anterior')
                    ->money('MXN')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Saldo Nuevo')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Realizado por')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'credit' => 'Carga',
                        'debit' => 'Descuento',
                        'adjustment' => 'Ajuste Manual',
                    ]),
                Tables\Filters\SelectFilter::make('gift_card_id')
                    ->label('Tarjeta')
                    ->relationship('giftCard', 'legacy_id')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalle de Transacción'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
