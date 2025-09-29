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
                                            ->dehydrated(false)
                                            ->prefixIcon('heroicon-m-lock-closed')
                                            ->helperText('Deja en blanco si no deseas cambiar la contraseña.')
                                            ->validationMessages([
                                                'confirmed' => 'Las contraseñas deben coincidir.',
                                            ]),
                                        Forms\Components\TextInput::make('password_confirmation')
                                            ->label('Confirmar Contraseña')
                                            ->password()
                                            ->dehydrated(false)
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
