<?php

namespace App\Repositories;

use App\Models\EximUser;
use App\Models\Domain;
use App\Repositories\Interfaces\EximUserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Repository implementation for EximUser model operations.
 * 
 * Handles all database interactions for the EximUser model,
 * including CRUD operations, search, filtering, and domain management.
 */
class EximUserRepository implements EximUserRepositoryInterface
{
    /**
     * @var EximUser The EximUser model instance
     */
    protected EximUser $model;
    
    /**
     * Constructor.
     * 
     * @param EximUser $model
     */
    public function __construct(EximUser $model)
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
    public function findById(int $id, array $relations = []): ?EximUser
    {
        return $this->model->with($relations)->find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByUsername(string $username, array $relations = []): ?EximUser
    {
        return $this->model->with($relations)->where('username', $username)->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByDomainId(int $domainId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('domain_id', $domainId)
            ->orderBy('localpart')
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByLocalpartAndDomain(string $localpart, int $domainId, array $relations = []): ?EximUser
    {
        return $this->model->with($relations)
            ->where('localpart', $localpart)
            ->where('domain_id', $domainId)
            ->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function create(array $data): EximUser
    {
        return DB::transaction(function () use ($data) {
            // Generate username from localpart and domain
            if (isset($data['localpart']) && isset($data['domain_id'])) {
                $domain = Domain::find($data['domain_id']);
                if ($domain) {
                    $data['username'] = $data['localpart'] . '@' . $domain->domain;
                }
            }
            
            // Set default values
            if (!isset($data['enabled'])) {
                $data['enabled'] = true;
            }
            
            if (!isset($data['type'])) {
                $data['type'] = 'local';
            }
            
            if (!isset($data['uid'])) {
                $data['uid'] = 8;
            }
            
            if (!isset($data['gid'])) {
                $data['gid'] = 8;
            }

            if (isset($data['crypt']) && !str_starts_with($data['crypt'], '$2y$')) {
                $data['crypt'] = Hash::make($data['crypt']);
            }
            
            return $this->model->create($data);
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): EximUser
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->findById($id);
            
            if (!$user) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("EximUser with ID {$id} not found");
            }

            if (isset($data['localpart']) || isset($data['domain_id'])) {
                $localpart = $data['localpart'] ?? $user->localpart;
                $domainId = $data['domain_id'] ?? $user->domain_id;
                $domain = Domain::find($domainId);
                
                if ($domain) {
                    $data['username'] = $localpart . '@' . $domain->domain;
                }
            }
            
            if (isset($data['crypt']) && !str_starts_with($data['crypt'], '$2y$')) {
                $data['crypt'] = Hash::make($data['crypt']);
            }
            
            $user->update($data);
            
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
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("EximUser with ID {$id} not found");
            }
            
            return $user->delete();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function enable(int $id): EximUser
    {
        $user = $this->findById($id);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("EximUser with ID {$id} not found");
        }
        
        $user->update(['enabled' => true]);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function disable(int $id): EximUser
    {
        $user = $this->findById($id);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("EximUser with ID {$id} not found");
        }
        
        $user->update(['enabled' => false]);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getEnabledUsers(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('enabled', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDisabledUsers(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('enabled', false)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByType(string $type, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('type', $type)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(array $criteria, array $relations = []): Collection
    {
        $query = $this->model->with($relations);
        
        // Search by username or localpart
        if (!empty($criteria['search'])) {
            $searchTerm = $criteria['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('username', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('localpart', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('realname', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Exact username match
        if (!empty($criteria['username'])) {
            $query->where('username', $criteria['username']);
        }
        
        // Partial username match
        if (!empty($criteria['username_like'])) {
            $query->where('username', 'LIKE', "%{$criteria['username_like']}%");
        }
        
        // Localpart search
        if (!empty($criteria['localpart'])) {
            $query->where('localpart', 'LIKE', "%{$criteria['localpart']}%");
        }
        
        // Filter by domain ID
        if (!empty($criteria['domain_id'])) {
            $query->where('domain_id', $criteria['domain_id']);
        }
        
        // Filter by domain name (join with domains table)
        if (!empty($criteria['domain_name'])) {
            $query->whereHas('domain', function ($q) use ($criteria) {
                $q->where('domain', 'LIKE', "%{$criteria['domain_name']}%");
            });
        }
        
        // Filter by type
        if (!empty($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }
        
        // Filter by enabled status
        if (isset($criteria['enabled'])) {
            $query->where('enabled', $criteria['enabled']);
        }
        
        // Filter by spam filtering
        if (isset($criteria['on_spamassassin'])) {
            $query->where('on_spamassassin', $criteria['on_spamassassin']);
        }
        
        // Filter by forwarding
        if (isset($criteria['on_forward'])) {
            $query->where('on_forward', $criteria['on_forward']);
        }
        
        // Filter by vacation
        if (isset($criteria['on_vacation'])) {
            $query->where('on_vacation', $criteria['on_vacation']);
        }
        
        // Filter by admin
        if (isset($criteria['admin'])) {
            $query->where('admin', $criteria['admin']);
        }
        
        // Filter by minimum quota
        if (!empty($criteria['min_quota'])) {
            $query->where('quota', '>=', $criteria['min_quota']);
        }
        
        // Filter by maximum quota
        if (!empty($criteria['max_quota'])) {
            $query->where('quota', '<=', $criteria['max_quota']);
        }
        
        // Filter by date range
        if (!empty($criteria['created_from'])) {
            $query->whereDate('created_at', '>=', $criteria['created_from']);
        }
        
        if (!empty($criteria['created_to'])) {
            $query->whereDate('created_at', '<=', $criteria['created_to']);
        }
        
        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'username';
        $sortOrder = $criteria['sort_order'] ?? 'asc';
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
                $q->where('username', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('localpart', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('realname', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        if (!empty($criteria['username'])) {
            $query->where('username', $criteria['username']);
        }
        
        if (!empty($criteria['username_like'])) {
            $query->where('username', 'LIKE', "%{$criteria['username_like']}%");
        }
        
        if (!empty($criteria['localpart'])) {
            $query->where('localpart', 'LIKE', "%{$criteria['localpart']}%");
        }
        
        if (!empty($criteria['domain_id'])) {
            $query->where('domain_id', $criteria['domain_id']);
        }
        
        if (!empty($criteria['domain_name'])) {
            $query->whereHas('domain', function ($q) use ($criteria) {
                $q->where('domain', 'LIKE', "%{$criteria['domain_name']}%");
            });
        }
        
        if (!empty($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }
        
        if (isset($criteria['enabled'])) {
            $query->where('enabled', $criteria['enabled']);
        }
        
        if (isset($criteria['on_spamassassin'])) {
            $query->where('on_spamassassin', $criteria['on_spamassassin']);
        }
        
        if (isset($criteria['on_forward'])) {
            $query->where('on_forward', $criteria['on_forward']);
        }
        
        if (isset($criteria['on_vacation'])) {
            $query->where('on_vacation', $criteria['on_vacation']);
        }
        
        if (isset($criteria['admin'])) {
            $query->where('admin', $criteria['admin']);
        }
        
        if (!empty($criteria['min_quota'])) {
            $query->where('quota', '>=', $criteria['min_quota']);
        }
        
        if (!empty($criteria['max_quota'])) {
            $query->where('quota', '<=', $criteria['max_quota']);
        }
        
        if (!empty($criteria['created_from'])) {
            $query->whereDate('created_at', '>=', $criteria['created_from']);
        }
        
        if (!empty($criteria['created_to'])) {
            $query->whereDate('created_at', '<=', $criteria['created_to']);
        }
        
        $sortBy = $criteria['sort_by'] ?? 'username';
        $sortOrder = $criteria['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);
        
        return $query->paginate($perPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getWithSpamFiltering(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('on_spamassassin', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getWithForwarding(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('on_forward', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getWithVacation(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('on_vacation', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function updatePassword(int $id, string $password): EximUser
    {
        $user = $this->findById($id);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("EximUser with ID {$id} not found");
        }
        
        $user->update(['crypt' => Hash::make($password)]);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateQuota(int $id, int $quota): EximUser
    {
        $user = $this->findById($id);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("EximUser with ID {$id} not found");
        }
        
        $user->update(['quota' => $quota]);
        
        return $user->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getQuotaUsage(int $id): array
    {
        $user = $this->findById($id);
        
        if (!$user) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("EximUser with ID {$id} not found");
        }
        
        $usageMB = 0;
        $percentage = 0;
        
        if ($user->quota > 0) {
            $percentage = ($usageMB / $user->quota) * 100;
        }
        
        return [
            'user_id' => $id,
            'username' => $user->username,
            'quota_limit_mb' => $user->quota,
            'quota_usage_mb' => $usageMB,
            'quota_percentage' => round($percentage, 2),
            'is_over_quota' => $percentage > 100,
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkEnable(array $userIds): int
    {
        return DB::transaction(function () use ($userIds) {
            $count = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $this->enable($userId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkDisable(array $userIds): int
    {
        return DB::transaction(function () use ($userIds) {
            $count = 0;
            
            foreach ($userIds as $userId) {
                try {
                    $this->disable($userId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    continue;
                }
            }
            
            return $count;
        });
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
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function existsByUsername(string $username, ?int $excludeId = null): bool
    {
        $query = $this->model->where('username', $username);
        
        if ($excludeId !== null) {
            $query->where('user_id', '!=', $excludeId);
        }
        
        return $query->exists();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainUserStatistics(int $domainId): array
    {
        $users = $this->findByDomainId($domainId);
        
        $totalUsers = $users->count();
        $enabledUsers = $users->where('enabled', true)->count();
        $disabledUsers = $users->where('enabled', false)->count();
        $localUsers = $users->where('type', 'local')->count();
        $forwardUsers = $users->where('type', 'forward')->count();
        
        $totalQuota = $users->sum('quota');
        $usersWithSpamFiltering = $users->where('on_spamassassin', true)->count();
        $usersWithForwarding = $users->where('on_forward', true)->count();
        $usersWithVacation = $users->where('on_vacation', true)->count();
        
        return [
            'domain_id' => $domainId,
            'total_users' => $totalUsers,
            'enabled_users' => $enabledUsers,
            'disabled_users' => $disabledUsers,
            'local_users' => $localUsers,
            'forward_users' => $forwardUsers,
            'total_quota_mb' => $totalQuota,
            'users_with_spam_filtering' => $usersWithSpamFiltering,
            'users_with_forwarding' => $usersWithForwarding,
            'users_with_vacation' => $usersWithVacation,
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUsersNearQuotaLimit(int $thresholdPercentage = 90, array $relations = []): Collection
    {
        // This needs to be implemented with actual quota usage data
        // For now, return empty collection
        return new Collection();
    }
	
    /**
     * {@inheritdoc}
     */
    public function getEnabledForAuth(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('enabled', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findEnabledByUsername(string $username, array $relations = []): ?EximUser
    {
        return $this->model->with($relations)
            ->where('username', $username)
            ->where('enabled', true)
            ->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function findEnabledById(int $id, array $relations = []): ?EximUser
    {
        return $this->model->with($relations)
            ->where('user_id', $id)
            ->where('enabled', true)
            ->first();
    }
	
    /**
     * {@inheritdoc}
     */
    public function findEnabledByRememberToken(string $token, array $relations = []): ?EximUser
    {
        return $this->model->with($relations)
            ->where('remember_token', $token)
            ->where('enabled', true)
            ->first();
    }	
    
    /**
     * {@inheritdoc}
     */
    public function getUsersWithSpamSettings(array $relations = []): Collection
    {
        $users = $this->model->with($relations)
            ->where('enabled', true)
            ->where('type', 'local')
            ->where(function ($query) {
                // Only get users who have at least one spam setting configured
                $query->whereNotNull('sa_tag')
                      ->orWhereNotNull('sa_refuse');
            })
            ->select([
                'user_id',
                'username',
                'localpart',
                'domain_id',
                'sa_tag',
                'sa_refuse',
                'on_spamassassin'
            ])
            ->orderBy('username')
            ->get();

        return $users;
    }  
}