<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static ?string $slug = 'accounts/website';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    
    protected static string|\UnitEnum|null $navigationGroup = 'Account Management';
    
    protected static ?string $navigationLabel = 'Website';    

    protected static ?string $recordTitleAttribute = 'email';
    
    protected static ?string $label = 'Website User';
    
    protected static ?string $pluralLabel = 'Website Users';
    
    protected static ?int $navigationSort = 15;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // System admins see all web users
        if ($user->isSystemAdmin()) {
            return $query;
        }

        // Domain admins only see:
        // 1. Themselves
        // 2. Other domain_admins within their domains
        if ($user->isDomainAdmin()) {
            // Specify the table for domain_id - use 'domain_user.domain_id'
            $domainIds = $user->domains()->pluck('domains.domain_id');

            return $query->where(function ($q) use ($user, $domainIds) {
                // The current user
                $q->where('users_web.id', $user->id)
                  // Other domain_admins who share the same domains
                  ->orWhereHas('domains', function ($dq) use ($domainIds) {
                      $dq->whereIn('domains.domain_id', $domainIds);
                  })
                  // Only show domain_admin role users
                  ->whereHas('roles', function ($rq) {
                      $rq->where('name', 'domain_admin');
                  });
            });
        }

        return $query->whereRaw('1 = 0');
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        
        if (!$user) return false;
        
        // System admins can edit anyone
        if ($user->isSystemAdmin()) return true;
        
        // Domain admins can only edit domain_admins within their domains
        if ($user->isDomainAdmin()) {
            // Get domains this admin manages
            $adminDomainIds = $user->domains()->pluck('domain_id');
            
            // Check if the target user is a domain_admin and shares at least one domain
            if ($record->hasRole('domain_admin')) {
                $recordDomainIds = $record->domains()->pluck('domain_id');
                return $recordDomainIds->intersect($adminDomainIds)->isNotEmpty();
            }
        }
        
        return false;
    }
    
    public static function canDelete($record): bool
    {
        $user = auth()->user();
        
        // Only system admins can delete users
        return $user && $user->isSystemAdmin();
    }    

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
    
    /**
     * Show badge with count of accessible users
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        try {
            if ($user->isSystemAdmin()) {
                // System admins see all web users
                $count = User::count();
            } elseif ($user->isDomainAdmin()) {
                // Domain admins see:
                // 1. Themselves
                // 2. Other domain_admins within their domains
                $domainIds = $user->domains()->pluck('domains.domain_id');

                $count = User::where(function ($q) use ($user, $domainIds) {
                    // The current user
                    $q->where('users_web.id', $user->id)
                      // Other domain_admins who share the same domains
                      ->orWhereHas('domains', function ($dq) use ($domainIds) {
                          $dq->whereIn('domains.domain_id', $domainIds);
                      })
                      // Only show domain_admin role users
                      ->whereHas('roles', function ($rq) {
                          $rq->where('name', 'domain_admin');
                      });
                })->count();
            } else {
                return null;
            }

            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Navigation badge error: ' . $e->getMessage());
            return null;
        }
    }    
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['name','email'];
    }      
}