<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Repository implementation for User model operations.
 * 
 * Handles all database interactions for the User model,
 * including CRUD operations, search, filtering, and role/domain management.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * @var User The User model instance
     */
    protected User $model;
    
    /**
     * Constructor.
     * 
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }
    
    /**
     * {@inheritdoc}
     */
    public function all(array $relations = []): Collection
    {
        return $this->model->with($relations)->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findById(int $id, array $relations = []): ?User
    {
        return $this->model->with($relations)->find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email, array $relations = []): ?User
    {
        return $this->model->with($relations)->where('email', $email)->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            
            // Set default active status if not provided
            if (!isset($data['active'])) {
                $data['active'] = true;
            }
            
            $user = $this->model->create($data);
            
            // Assign role if provided
            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }
            
            // Assign domains if provided
            if (isset($data['domains']) && is_array($data['domains'])) {
                foreach ($data['domains'] as $domainId => $role) {
                    $user->domains()->attach($domainId, ['role' => $role]);
                }
            }
            
            return $user->fresh();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->findById($id);
            
            if (!$user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$id} not found");
            }
            
            // Hash password if provided and not empty
            if (isset($data['password'])) {
                if (empty($data['password'])) {
                    unset($data['password']);
                } else {
                    $data['password'] = Hash::make($data['password']);
                }
            }
            
            $user->update($data);
            
            // Update role if provided
            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }
            
            // Update domain assignments if provided
            if (isset($data['domains'])) {
                $syncData = [];
                foreach ($data['domains'] as $domainId => $role) {
                    $syncData[$domainId] = ['role' => $role];
                }
                $user->domains()->sync($syncData);
            }
            
            return $user->fresh();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->findById($id);
            
            if (!$user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$id} not found");
            }
            
            // Detach all relationships
            $user->domains()->detach();
            $user->roles()->detach();
            
            // Delete the user
            return $user->delete();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(array $criteria, array $relations = []): Collection
    {
        $query = $this->model->with($relations);
        
        // Apply search filters
        if (!empty($criteria['search'])) {
            $searchTerm = $criteria['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Filter by name
        if (!empty($criteria['name'])) {
            $query->where('name', 'LIKE', "%{$criteria['name']}%");
        }
        
        // Exact email match
        if (!empty($criteria['email'])) {
            $query->where('email', $criteria['email']);
        }
        
        // Partial email match
        if (!empty($criteria['email_like'])) {
            $query->where('email', 'LIKE', "%{$criteria['email_like']}%");
        }
        
        // Filter by role
        if (!empty($criteria['role'])) {
            $query->role($criteria['role']);
        }
        
        // Filter by domain ID
        if (!empty($criteria['domain_id'])) {
            $query->whereHas('domains', function ($q) use ($criteria) {
                $q->where('domain_id', $criteria['domain_id']);
            });
        }
        
        // Filter by date range
        if (!empty($criteria['created_from'])) {
            $query->whereDate('created_at', '>=', $criteria['created_from']);
        }
        
        if (!empty($criteria['created_to'])) {
            $query->whereDate('created_at', '<=', $criteria['created_to']);
        }
        
        // Email verified status
        if (isset($criteria['email_verified'])) {
            if ($criteria['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }
        
        // Add active status filter
        if (isset($criteria['active'])) {
            if ($criteria['active'] === 'active') {
                $query->where('active', true);
            } elseif ($criteria['active'] === 'inactive') {
                $query->where('active', false);
            } elseif (is_bool($criteria['active'])) {
                $query->where('active', $criteria['active']);
            }
        }
        
        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'created_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        return $query->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function searchPaginated(array $criteria, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->with($relations);
        
        if (!empty($criteria['search'])) {
            $searchTerm = $criteria['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        if (!empty($criteria['name'])) {
            $query->where('name', 'LIKE', "%{$criteria['name']}%");
        }
        
        if (!empty($criteria['email'])) {
            $query->where('email', $criteria['email']);
        }
        
        if (!empty($criteria['email_like'])) {
            $query->where('email', 'LIKE', "%{$criteria['email_like']}%");
        }
        
        if (!empty($criteria['role'])) {
            $query->role($criteria['role']);
        }
        
        if (!empty($criteria['domain_id'])) {
            $query->whereHas('domains', function ($q) use ($criteria) {
                $q->where('domain_id', $criteria['domain_id']);
            });
        }
        
        if (!empty($criteria['created_from'])) {
            $query->whereDate('created_at', '>=', $criteria['created_from']);
        }
        
        if (!empty($criteria['created_to'])) {
            $query->whereDate('created_at', '<=', $criteria['created_to']);
        }
        
        if (isset($criteria['email_verified'])) {
            if ($criteria['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }
        
        // Add active status filter
        if (isset($criteria['active'])) {
            if ($criteria['active'] === 'active') {
                $query->where('active', true);
            } elseif ($criteria['active'] === 'inactive') {
                $query->where('active', false);
            } elseif (is_bool($criteria['active'])) {
                $query->where('active', $criteria['active']);
            }
        }
        
        $sortBy = $criteria['sort_by'] ?? 'created_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        return $query->paginate($perPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByRole(string $role, array $relations = []): Collection
    {
        return $this->model->with($relations)->role($role)->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByDomain(int $domainId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->whereHas('domains', function ($query) use ($domainId) {
                $query->where('domain_id', $domainId);
            })
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSystemAdmins(array $relations = []): Collection
    {
        return $this->getByRole('system_admin', $relations);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainAdmins(array $relations = []): Collection
    {
        return $this->getByRole('domain_admin', $relations);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainUsers(array $relations = []): Collection
    {
        return $this->getByRole('domain_user', $relations);
    }
    
    /**
     * {@inheritdoc}
     */
    public function assignRole(int $userId, string $role): User
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$userId} not found");
        }
        
        $user->syncRoles([$role]);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function assignDomain(int $userId, int $domainId, string $role = 'viewer'): User
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$userId} not found");
        }
        
        $user->domains()->syncWithoutDetaching([$domainId => ['role' => $role]]);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeDomain(int $userId, int $domainId): User
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$userId} not found");
        }
        
        $user->domains()->detach($domainId);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getWithDomains(array $relations = []): Collection
    {
        return $this->model->with(array_merge(['domains'], $relations))->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByRole(): array
    {
        $roles = Role::all();
        $counts = [];
        
        foreach ($roles as $role) {
            $counts[$role->name] = $this->model->role($role->name)->count();
        }
        
        return $counts;
    }
    
    /**
     * {@inheritdoc}
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);
        
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $userIds): int
    {
        return DB::transaction(function () use ($userIds) {
            $count = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $this->delete($userId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    // Skip non-existent users
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateLastLogin(int $userId): User
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$userId} not found");
        }
        
        $user->update(['last_login_at' => now()]);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function activate(int $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = $this->findById($id);
            
            if (!$user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$id} not found");
            }
            
            $updateData = ['active' => true];
            
            if (Schema::hasColumn('users_web', 'deactivated_at')) {
                $updateData['deactivated_at'] = null;
            }
            
            $user->update($updateData);
            
            if (method_exists($user, 'activities')) {
                activity()
                    ->performedOn($user)
                    ->withProperties(['action' => 'activate'])
                    ->log('User was activated');
            }
            
            return $user->fresh();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function deactivate(int $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = $this->findById($id);
            
            if (!$user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$id} not found");
            }
            
            // Prevent deactivating the last system admin
            if ($user->isSystemAdmin() && $this->getActiveSystemAdminsCount() <= 1) {
                throw new \RuntimeException('Cannot deactivate the last active system administrator');
            }
            
            $updateData = ['active' => false];
            
            if (Schema::hasColumn('users_web', 'deactivated_at')) {
                $updateData['deactivated_at'] = now();
            }
            
            $user->update($updateData);
            
            // Revoke all API tokens when deactivating (security measure)
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            
            // Log the deactivation if activity log is enabled
            if (method_exists($user, 'activities')) {
                activity()
                    ->performedOn($user)
                    ->withProperties(['action' => 'deactivate'])
                    ->log('User was deactivated');
            }
            
            return $user->fresh();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function toggleActive(int $id): User
    {
        $user = $this->findById($id);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("User with ID {$id} not found");
        }
        
        if ($user->active) {
            return $this->deactivate($id);
        } else {
            return $this->activate($id);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getActiveUsers(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('active', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getInactiveUsers(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('active', false)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkActivate(array $userIds): int
    {
        return DB::transaction(function () use ($userIds) {
            $count = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $this->activate($userId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    continue;
                } catch (\RuntimeException $e) {
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkDeactivate(array $userIds): int
    {
        return DB::transaction(function () use ($userIds) {
            $count = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $this->deactivate($userId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    continue;
                } catch (\RuntimeException $e) {
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function getActiveUsersPaginated(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)
            ->where('active', true)
            ->paginate($perPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getInactiveUsersPaginated(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)
            ->where('active', false)
            ->paginate($perPage);
    }
    
    /**
     * Helper method to get count of active system administrators.
     * 
     * @return int
     */
    private function getActiveSystemAdminsCount(): int
    {
        return $this->model->role('system_admin')
            ->where('active', true)
            ->count();
    }
	
    /**
     * {@inheritdoc}
     */
    public function getActiveForAuth(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('active', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findActiveByEmail(string $email, array $relations = []): ?User
    {
        return $this->model->with($relations)
            ->where('email', $email)
            ->where('active', true)
            ->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findActiveById(int $id, array $relations = []): ?User
    {
        return $this->model->with($relations)
            ->where('id', $id)
            ->where('active', true)
            ->first();
    }
	
    /**
     * {@inheritdoc}
     */
    public function findActiveByRememberToken(string $token, array $relations = []): ?User
    {
        return $this->model->with($relations)
            ->where('remember_token', $token)
            ->where('active', true)
            ->first();
    }	
}