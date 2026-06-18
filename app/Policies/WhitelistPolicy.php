<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Whitelist;
use Illuminate\Auth\Access\HandlesAuthorization;

class WhitelistPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Whitelist');
    }

    public function view(AuthUser $authUser, Whitelist $whitelist): bool
    {
        return $authUser->can('View:Whitelist');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Whitelist');
    }

    public function update(AuthUser $authUser, Whitelist $whitelist): bool
    {
        return $authUser->can('Update:Whitelist');
    }

    public function delete(AuthUser $authUser, Whitelist $whitelist): bool
    {
        return $authUser->can('Delete:Whitelist');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Whitelist');
    }

    public function restore(AuthUser $authUser, Whitelist $whitelist): bool
    {
        return $authUser->can('Restore:Whitelist');
    }

    public function forceDelete(AuthUser $authUser, Whitelist $whitelist): bool
    {
        return $authUser->can('ForceDelete:Whitelist');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Whitelist');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Whitelist');
    }

    public function replicate(AuthUser $authUser, Whitelist $whitelist): bool
    {
        return $authUser->can('Replicate:Whitelist');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Whitelist');
    }

}