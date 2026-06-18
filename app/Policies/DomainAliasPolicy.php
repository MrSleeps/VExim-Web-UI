<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DomainAlias;
use Illuminate\Auth\Access\HandlesAuthorization;

class DomainAliasPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DomainAlias');
    }

    public function view(AuthUser $authUser, DomainAlias $domainAlias): bool
    {
        return $authUser->can('View:DomainAlias');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DomainAlias');
    }

    public function update(AuthUser $authUser, DomainAlias $domainAlias): bool
    {
        return $authUser->can('Update:DomainAlias');
    }

    public function delete(AuthUser $authUser, DomainAlias $domainAlias): bool
    {
        return $authUser->can('Delete:DomainAlias');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DomainAlias');
    }

    public function restore(AuthUser $authUser, DomainAlias $domainAlias): bool
    {
        return $authUser->can('Restore:DomainAlias');
    }

    public function forceDelete(AuthUser $authUser, DomainAlias $domainAlias): bool
    {
        return $authUser->can('ForceDelete:DomainAlias');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DomainAlias');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DomainAlias');
    }

    public function replicate(AuthUser $authUser, DomainAlias $domainAlias): bool
    {
        return $authUser->can('Replicate:DomainAlias');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DomainAlias');
    }

}