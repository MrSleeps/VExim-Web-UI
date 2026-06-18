<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class EximUserPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EximUser');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:EximUser');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EximUser');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:EximUser');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:EximUser');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EximUser');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:EximUser');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:EximUser');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EximUser');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EximUser');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:EximUser');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EximUser');
    }

}