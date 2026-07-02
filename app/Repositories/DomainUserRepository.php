<?php

namespace App\Repositories;

use App\Models\DomainUser;
use VEximweb\Core\Data\Models\User;
use VEximweb\Core\Data\Models\Domain;
use App\Repositories\Interfaces\DomainUserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository implementation for DomainUser model operations.
 * 
 * Handles all database interactions for the domain_user pivot table,
 * managing relationships between users and domains.
 */
class DomainUserRepository implements DomainUserRepositoryInterface
{
    /**
     * @var DomainUser The DomainUser model instance
     */
    protected DomainUser $model;
    
    /**
     * Constructor.
     * 
     * @param DomainUser $model
     */
    public function __construct(DomainUser $model)
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
    public function findById(int $id, array $relations = []): ?DomainUser
    {
        return $this->model->with($relations)->find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUserAndDomain(int $userId, int $domainId, array $relations = []): ?DomainUser
    {
        return $this->model->with($relations)
            ->where('user_id', $userId)
            ->where('domain_id', $domainId)
            ->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function create(array $data): DomainUser
    {
        return DB::transaction(function () use ($data) {
            $existing = $this->findByUserAndDomain($data['user_id'], $data['domain_id']);
            
            if ($existing) {
                throw new \InvalidArgumentException(
                    "Relationship between user {$data['user_id']} and domain {$data['domain_id']} already exists"
                );
            }

            if (!isset($data['role'])) {
                $data['role'] = 'domain_admin';
            }
            
            return $this->model->create($data);
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): DomainUser
    {
        return DB::transaction(function () use ($id, $data) {
            $relationship = $this->findById($id);
            
            if (!$relationship) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "DomainUser relationship with ID {$id} not found"
                );
            }
            
            $relationship->update($data);
            
            return $relationship->fresh();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $relationship = $this->findById($id);
            
            if (!$relationship) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                    "DomainUser relationship with ID {$id} not found"
                );
            }
            
            return $relationship->delete();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteByUserAndDomain(int $userId, int $domainId): bool
    {
        return DB::transaction(function () use ($userId, $domainId) {
            return $this->model->where('user_id', $userId)
                ->where('domain_id', $domainId)
                ->delete() > 0;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByUser(int $userId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('user_id', $userId)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByDomain(int $domainId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('domain_id', $domainId)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainAdmins(int $domainId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('domain_id', $domainId)
            ->where('role', 'domain_admin')
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUserDomains(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->with('domain')
            ->get()
            ->pluck('domain');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainUsers(int $domainId): Collection
    {
        return $this->model->where('domain_id', $domainId)
            ->with('user')
            ->get()
            ->pluck('user');
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateOrCreate(int $userId, int $domainId, string $role = 'domain_admin'): DomainUser
    {
        return DB::transaction(function () use ($userId, $domainId, $role) {
            return $this->model->updateOrCreate(
                [
                    'user_id' => $userId,
                    'domain_id' => $domainId,
                ],
                [
                    'role' => $role,
                ]
            );
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function userHasAccess(int $userId, int $domainId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('domain_id', $domainId)
            ->exists();
    }
    
    /**
     * {@inheritdoc}
     */
    public function userIsDomainAdmin(int $userId, int $domainId): bool
    {
        return $this->model->where('user_id', $userId)
            ->where('domain_id', $domainId)
            ->where('role', 'domain_admin')
            ->exists();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUserRoleForDomain(int $userId, int $domainId): ?string
    {
        $relationship = $this->findByUserAndDomain($userId, $domainId);
        
        return $relationship ? $relationship->role : null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkAssignUsers(int $domainId, array $userIds, string $role = 'domain_admin'): int
    {
        return DB::transaction(function () use ($domainId, $userIds, $role) {
            $count = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $user = User::find($userId);
                    if (!$user) {
                        continue;
                    }

                    $domain = Domain::find($domainId);
                    if (!$domain) {
                        continue;
                    }

                    $this->updateOrCreate($userId, $domainId, $role);
                    $count++;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkRemoveUsers(int $domainId, array $userIds): int
    {
        return DB::transaction(function () use ($domainId, $userIds) {
            return $this->model->where('domain_id', $domainId)
                ->whereIn('user_id', $userIds)
                ->delete();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByRole(string $role, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('role', $role)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByDomain(int $domainId): int
    {
        return $this->model->where('domain_id', $domainId)->count();
    }
    
    /**
     * {@inheritdoc}
     */
    public function countByUser(int $userId): int
    {
        return $this->model->where('user_id', $userId)->count();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainsWithoutAssignments(array $relations = []): Collection
    {
        $assignedDomainIds = $this->model->distinct()->pluck('domain_id')->toArray();
        
        return Domain::with($relations)
            ->whereNotIn('domain_id', $assignedDomainIds)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUsersWithoutAssignments(array $relations = []): Collection
    {
        $assignedUserIds = $this->model->distinct()->pluck('user_id')->toArray();
        
        return User::with($relations)
            ->whereNotIn('id', $assignedUserIds)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(array $criteria, array $relations = []): Collection
    {
        $query = $this->model->with($relations);
        
        // Filter by user ID
        if (!empty($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }
        
        // Filter by domain ID
        if (!empty($criteria['domain_id'])) {
            $query->where('domain_id', $criteria['domain_id']);
        }
        
        // Filter by role
        if (!empty($criteria['role'])) {
            $query->where('role', $criteria['role']);
        }
        
        // Filter by date range - created from
        if (!empty($criteria['created_from'])) {
            $query->whereDate('created_at', '>=', $criteria['created_from']);
        }
        
        // Filter by date range - created to
        if (!empty($criteria['created_to'])) {
            $query->whereDate('created_at', '<=', $criteria['created_to']);
        }
        
        // Search by user name or email (joins with users table)
        if (!empty($criteria['user_search'])) {
            $searchTerm = $criteria['user_search'];
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Search by domain name (joins with domains table)
        if (!empty($criteria['domain_search'])) {
            $searchTerm = $criteria['domain_search'];
            $query->whereHas('domain', function ($q) use ($searchTerm) {
                $q->where('domain', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'created_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        return $query->get();
    }
}