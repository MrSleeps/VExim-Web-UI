<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Models\EximUser;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';
    
    protected static ?string $recordTitleAttribute = 'username';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add Member')
                    ->model(EximUser::class)
                    ->recordSelectOptionsQuery(function ($query) {
                        $user = auth()->user();
                        $group = $this->getOwnerRecord();

                        $query = $query->whereIn('type', ['alias', 'local']);
                        
                        if ($user->isSystemAdmin()) {
                            return $query;
                        }
                        
                        if ($user->isDomainAdmin()) {
                            $domainIds = $user->domains()->pluck('domain_id');
                            
                            if ($group && $group->domain_id) {
                                $domainIds->push($group->domain_id);
                            }
                            
                            return $query->whereIn('domain_id', $domainIds);
                        }
                        
                        return $query->whereRaw('1 = 0');
                    })
                    ->recordTitleAttribute('username')
                    ->preloadRecordSelect(),
            ])
            ->actions([                
                DetachAction::make()
                    ->label('Remove'),
            ])            
            ->emptyStateHeading('No group members')
            ->emptyStateDescription('Add members to this group by clicking the "Add Member" button above.');
    }
}