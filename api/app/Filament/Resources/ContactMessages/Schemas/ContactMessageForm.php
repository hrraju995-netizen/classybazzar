<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'xl' => 3])
                    ->schema([
                        Group::make([
                            Section::make('Message content')
                                ->description('Inquiry submitted through the storefront contact form.')
                                ->icon('heroicon-o-chat-bubble-left-right')
                                ->schema([
                                    TextInput::make('subject')
                                        ->label('Subject')
                                        ->placeholder('Inquiry subject')
                                        ->prefixIcon('heroicon-o-chat-bubble-oval-left-ellipsis')
                                        ->maxLength(200),

                                    Textarea::make('message')
                                        ->label('Message body')
                                        ->required()
                                        ->rows(7)
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(['xl' => 2]),

                        Group::make([
                            Section::make('Sender details')
                                ->description('Customer contact details and read status.')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Sender name')
                                        ->prefixIcon('heroicon-o-user')
                                        ->required(),

                                    TextInput::make('email')
                                        ->label('Email address')
                                        ->prefixIcon('heroicon-o-envelope')
                                        ->email(),

                                    TextInput::make('phone')
                                        ->label('Phone number')
                                        ->prefixIcon('heroicon-o-phone')
                                        ->tel(),

                                    Toggle::make('is_read')
                                        ->label('Marked as read')
                                        ->default(false)
                                        ->helperText('Turn on once this inquiry has been handled.'),
                                ]),
                        ])->columnSpan(['xl' => 1]),
                    ]),
            ]);
    }
}
