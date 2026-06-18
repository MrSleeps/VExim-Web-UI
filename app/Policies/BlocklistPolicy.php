<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Blocklist;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlocklistPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Blocklist');
    }

    public function view(AuthUser $authUser, Blocklist $blocklist): bool
    {
        return $authUser->can('View:Blocklist');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Blocklist');
    }

    public function update(AuthUser $authUser, Blocklist $blocklist): bool
    {
        return $authUser->can('Update:Blocklist');
    }

    public function delete(AuthUser $authUser, Blocklist $blocklist): bool
    {
        return $authUser->can('Delete:Blocklist');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Blocklist');
    }

    public function restore(AuthUser $authUser, Blocklist $blocklist): bool
    {
        return $authUser->can('Restore:Blocklist');
    }

    public function forceDelete(AuthUser $authUser, Blocklist $blocklist): bool
    {
        return $authUser->can('ForceDelete:Blocklist');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Blocklist');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Blocklist');
    }

    public function replicate(AuthUser $authUser, Blocklist $blocklist): bool
    {
        return $authUser->can('Replicate:Blocklist');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Blocklist');
    }

}