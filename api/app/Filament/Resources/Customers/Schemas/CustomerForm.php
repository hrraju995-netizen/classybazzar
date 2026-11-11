<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])->schema([
                    Group::make([
                        Section::make('Customer profile')
                            ->description('Primary contact and delivery details for this customer.')
                            ->icon('heroicon-o-user')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full name')
                                    ->prefixIcon('heroicon-o-user')
                                    ->required()
                                    ->maxLength(120),

                                TextInput::make('phone')
                                    ->label('Mobile number')
                                    ->prefixIcon('heroicon-o-phone')
                                    ->placeholder('017XXXXXXXX')
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true),

                                TextInput::make('email')
                                    ->label('Email address')
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->email()
                                    ->maxLength(180)
                                    ->unique(ignoreRecord: true),

                                TextInput::make('city')
                                    ->label('City / District')
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->placeholder('Dhaka')
                                    ->maxLength(100),

                                Textarea::make('address')
                                    ->label('Full delivery address')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['xl' => 2]),

                    Group::make([
                        Section::make('Account & security')
                            ->description('Sign-in method and optional password reset.')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                Select::make('auth_provider')
                                    ->label('Sign-in method')
                                    ->native(false)
                                    ->options([
                                        'email' => 'Email & Password',
                                        'phone' => 'Phone Number',
                                        'google' => 'Google Account',
                                    ])
                                    ->default('phone'),

                                TextInput::make('password')
                                    ->label('Password')
                                    ->prefixIcon('heroicon-o-key')
                                    ->password()
                                    ->revealable()
                                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->helperText('Leave blank when editing to keep the current password.'),
                            ]),
                    ])->columnSpan(['xl' => 1]),
                ]),
            ]);
    }
}

