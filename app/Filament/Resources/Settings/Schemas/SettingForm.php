<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            TextInput::make('key')
                ->required()
                ->readOnly(function ($record) {
                    if (!$record) {
                        return false;
                    }
                    return $record->can_delete == 0;
                })
                ->helperText(function ($record) {
                    if ($record && $record->can_delete == 0) {
                        return 'This key cannot be modified because this setting is protected.';
                    }
                    return null;
                }),
                Textarea::make('value')
                    ->required()
                    ->columnSpanFull(),

                Select::make('type')
                    ->options(['string' => 'String', 'integer' => 'Integer', 'boolean' => 'Boolean', 'json' => 'Json'])
                    ->default('string')
                    ->required()
                    ->disabled(function ($record) {
                        return $record && $record->can_delete == 0;
                    })
                    ->helperText(function ($record) {
                        if ($record && $record->can_delete == 0) {
                            return 'This type cannot be changed because this setting is protected.';
                        }
                        return null;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        return $state;
                    }),
                
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}