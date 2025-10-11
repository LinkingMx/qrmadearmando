<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    /**
     * Spanish menus and icons.
     */
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administración de sistema';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        // Sección lateral pequeña - Solo foto y nombre no editable
                        Section::make('Perfil')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Forms\Components\FileUpload::make('avatar')
                                    ->label('')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('avatars')
                                    ->alignCenter()
                                    ->imagePreviewHeight('200')
                                    ->downloadable()
                                    ->acceptedFileTypes(['image/png', 'image/jpg', 'image/jpeg', 'image/gif'])
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('display_name')
                                    ->label('')
                                    ->content(fn ($record) => $record?->name ?? '')
                                    ->extraAttributes(['class' => 'text-center'])
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('display_email')
                                    ->label('')
                                    ->content(fn ($record) => $record?->email ?? '')
                                    ->extraAttributes(['class' => 'text-center'])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(1),

                        // Sección principal - Información del usuario completa
                        Section::make('Información del Usuario')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-user')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->columnSpanFull(),
                                Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('password')
                                            ->label('Contraseña')
                                            ->password()
                                            ->confirmed()
                                            ->minLength(8)
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->prefixIcon('heroicon-m-lock-closed')
                                            ->live(onBlur: true)
                                            ->helperText(fn (string $operation): string =>
                                                $operation === 'create'
                                                    ? 'Ingrese una contraseña segura (mínimo 8 caracteres).'
                                                    : 'Deja en blanco si no deseas cambiar la contraseña.'
                                            )
                                            ->validationMessages([
                                                'confirmed' => 'Las contraseñas deben coincidir.',
                                                'min' => 'La contraseña debe tener al menos 8 caracteres.',
                                            ]),
                                        Forms\Components\TextInput::make('password_confirmation')
                                            ->label('Confirmar Contraseña')
                                            ->password()
                                            ->dehydrated(false)
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->prefixIcon('heroicon-m-lock-closed'),
                                    ]),
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccionar sucursal')
                                    ->prefixIcon('heroicon-m-building-office-2')
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Usuario Activo')
                                    ->default(true)
                                    ->helperText('Los usuarios inactivos no podrán iniciar sesión')
                                    ->disabled(fn ($record) => $record && $record->id === auth()->id())
                                    ->dehydrated(fn ($record) => !$record || $record->id !== auth()->id())
                                    ->inline(false)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                ->label('Foto')
                ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin asignar'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('two_factor_confirmed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('branch')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos los usuarios')
                    ->trueLabel('Solo usuarios activos')
                    ->falseLabel('Solo usuarios inactivos')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (User $record): string => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->hidden(fn (User $record): bool => $record->id === auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_active ? 'Desactivar Usuario' : 'Activar Usuario')
                    ->modalDescription(fn (User $record): string => $record->is_active
                        ? '¿Estás seguro de que deseas desactivar este usuario? No podrá iniciar sesión y todos sus QR codes serán desactivados hasta que sea reactivado.'
                        : '¿Estás seguro de que deseas activar este usuario? Podrá iniciar sesión nuevamente y todos sus QR codes serán reactivados.')
                    ->modalSubmitActionLabel(fn (User $record): string => $record->is_active ? 'Desactivar' : 'Activar')
                    ->action(function (User $record) {
                        if ($record->id === auth()->id()) {
                            \Filament\Notifications\Notification::make()
                                ->title('No puedes desactivar tu propia cuenta')
                                ->danger()
                                ->send();
                            return;
                        }

                        $giftCardsCount = $record->giftCards()->count();
                        $record->is_active = !$record->is_active;
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title($record->is_active
                                ? 'Usuario activado correctamente'
                                : 'Usuario desactivado correctamente')
                            ->body($giftCardsCount > 0
                                ? ($record->is_active
                                    ? "{$giftCardsCount} QR code(s) activados"
                                    : "{$giftCardsCount} QR code(s) desactivados")
                                : 'Este usuario no tiene QR codes asignados')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->color('primary'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->color('warning'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
