<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sampah;
use Illuminate\Auth\Access\HandlesAuthorization;

class SampahPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_sampah');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sampah $sampah): bool
    {
        return $user->can('view_sampah');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_sampah');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sampah $sampah): bool
    {
        return $user->can('update_sampah');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sampah $sampah): bool
    {
        return $user->can('delete_sampah');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_sampah');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Sampah $sampah): bool
    {
        return $user->can('force_delete_sampah');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_sampah');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Sampah $sampah): bool
    {
        return $user->can('restore_sampah');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_sampah');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Sampah $sampah): bool
    {
        return $user->can('replicate_sampah');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_sampah');
    }
}
