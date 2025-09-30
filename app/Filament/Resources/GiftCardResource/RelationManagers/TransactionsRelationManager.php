<?php

namespace App\Filament\Resources\GiftCardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Historial de Transacciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options([
                        'credit' => 'Carga',
                        'debit' => 'Descuento',
                        'adjustment' => 'Ajuste Manual',
                    ])
                    ->required()
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('balance_before')
                    ->label('Saldo Anterior')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('balance_after')
                    ->label('Saldo Nuevo')
                    ->numeric()
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('admin_user_id')
                    ->relationship('admin', 'name')
                    ->label('Usuario Admin')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
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
                    ->sortable(),
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
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('Realizado por')
                    ->sortable(),
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
            ])
            ->headerActions([
                // No permitir crear desde aquí - se usa las acciones personalizadas
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalle de Transacción'),
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
