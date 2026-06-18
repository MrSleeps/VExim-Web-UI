<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use FinityLabs\FinMail\Models\EmailTheme;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailThemePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmailTheme');
    }

    public function view(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('View:EmailTheme');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmailTheme');
    }

    public function update(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('Update:EmailTheme');
    }

    public function delete(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('Delete:EmailTheme');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EmailTheme');
    }

    public function restore(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('Restore:EmailTheme');
    }

    public function forceDelete(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('ForceDelete:EmailTheme');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EmailTheme');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EmailTheme');
    }

    public function replicate(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('Replicate:EmailTheme');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EmailTheme');
    }

}