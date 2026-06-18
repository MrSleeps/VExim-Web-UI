<?php

namespace App\Repositories;

use App\Models\Domain;
use App\Models\User;
use App\Repositories\Interfaces\DomainRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Repository implementation for Domain model operations.
 * 
 * Handles all database interactions for the Domain model,
 * including CRUD operations, search, filtering, and user assignments.
 */
class DomainRepository implements DomainRepositoryInterface
{
    /**
     * @var Domain The Domain model instance
     */
    protected Domain $model;
    
    /**
     * Constructor.
     * 
     * @param Domain $model
     */
    public function __construct(Domain $model)
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
    public function findById(int $id, array $relations = []): ?Domain
    {
        return $this->model->with($relations)->find($id);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findByDomainName(string $domainName, array $relations = []): ?Domain
    {
        return $this->model->with($relations)->where('domain', $domainName)->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function create(array $data): Domain
    {
        return DB::transaction(function () use ($data) {
            // Set default values if not provided
            if (!isset($data['enabled'])) {
                $data['enabled'] = true;
            }
            
            if (!isset($data['type'])) {
                $data['type'] = 'local';
            }

            $domain = $this->model->create($data);

            if (isset($data['users']) && is_array($data['users'])) {
                foreach ($data['users'] as $userId) {
                    $domain->administrators()->attach($userId, ['role' => 'domain_admin']);
                }
            }
            
            return $domain->fresh();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): Domain
    {
        return DB::transaction(function () use ($id, $data) {
            $domain = $this->findById($id);
            
            if (!$domain) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$id} not found");
            }
            
            $domain->update($data);

            if (isset($data['users'])) {
                $syncData = [];
                foreach ($data['users'] as $userId => $role) {
                    $syncData[$userId] = ['role' => $role];
                }
                $domain->administrators()->sync($syncData);
            }
            
            return $domain->fresh();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $domain = $this->findById($id);
            
            if (!$domain) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$id} not found");
            }

            $domain->administrators()->detach();

            return $domain->delete();
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function search(array $criteria, array $relations = []): Collection
    {
        $query = $this->model->with($relations);
        
        // Search by domain name (partial match)
        if (!empty($criteria['search'])) {
            $searchTerm = $criteria['search'];
            $query->where('domain', 'LIKE', "%{$searchTerm}%");
        }
        
        // Exact domain name match
        if (!empty($criteria['domain'])) {
            $query->where('domain', $criteria['domain']);
        }
        
        // Partial domain name match
        if (!empty($criteria['domain_like'])) {
            $query->where('domain', 'LIKE', "%{$criteria['domain_like']}%");
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
        if (isset($criteria['spamassassin'])) {
            $query->where('spamassassin', $criteria['spamassassin']);
        }
        
        // Filter by virus scanning
        if (isset($criteria['avscan'])) {
            $query->where('avscan', $criteria['avscan']);
        }
        
        // Filter by blocklists
        if (isset($criteria['blocklists'])) {
            $query->where('blocklists', $criteria['blocklists']);
        }
        
        // Filter by minimum max_accounts
        if (!empty($criteria['min_accounts'])) {
            $query->where('max_accounts', '>=', $criteria['min_accounts']);
        }
        
        // Filter by maximum max_accounts
        if (!empty($criteria['max_accounts'])) {
            $query->where('max_accounts', '<=', $criteria['max_accounts']);
        }
        
        // Filter by minimum quota
        if (!empty($criteria['min_quota'])) {
            $query->where('quotas', '>=', $criteria['min_quota']);
        }
        
        // Filter by maximum quota
        if (!empty($criteria['max_quota'])) {
            $query->where('quotas', '<=', $criteria['max_quota']);
        }
        
        // Filter by user assignment
        if (!empty($criteria['assigned_user_id'])) {
            $query->whereHas('administrators', function ($q) use ($criteria) {
                $q->where('user_id', $criteria['assigned_user_id']);
            });
        }
        
        // Filter by no administrators assigned
        if (isset($criteria['without_admins']) && $criteria['without_admins']) {
            $query->doesntHave('administrators');
        }
        
        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'domain';
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
        
        // Apply same filters as search method
        if (!empty($criteria['search'])) {
            $searchTerm = $criteria['search'];
            $query->where('domain', 'LIKE', "%{$searchTerm}%");
        }
        
        if (!empty($criteria['domain'])) {
            $query->where('domain', $criteria['domain']);
        }
        
        if (!empty($criteria['domain_like'])) {
            $query->where('domain', 'LIKE', "%{$criteria['domain_like']}%");
        }
        
        if (!empty($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }
        
        if (isset($criteria['enabled'])) {
            $query->where('enabled', $criteria['enabled']);
        }
        
        if (isset($criteria['spamassassin'])) {
            $query->where('spamassassin', $criteria['spamassassin']);
        }
        
        if (isset($criteria['avscan'])) {
            $query->where('avscan', $criteria['avscan']);
        }
        
        if (isset($criteria['blocklists'])) {
            $query->where('blocklists', $criteria['blocklists']);
        }
        
        if (!empty($criteria['min_accounts'])) {
            $query->where('max_accounts', '>=', $criteria['min_accounts']);
        }
        
        if (!empty($criteria['max_accounts'])) {
            $query->where('max_accounts', '<=', $criteria['max_accounts']);
        }
        
        if (!empty($criteria['min_quota'])) {
            $query->where('quotas', '>=', $criteria['min_quota']);
        }
        
        if (!empty($criteria['max_quota'])) {
            $query->where('quotas', '<=', $criteria['max_quota']);
        }
        
        if (!empty($criteria['assigned_user_id'])) {
            $query->whereHas('administrators', function ($q) use ($criteria) {
                $q->where('user_id', $criteria['assigned_user_id']);
            });
        }
        
        if (isset($criteria['without_admins']) && $criteria['without_admins']) {
            $query->doesntHave('administrators');
        }
        
        $sortBy = $criteria['sort_by'] ?? 'domain';
        $sortOrder = $criteria['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);
        
        return $query->paginate($perPage);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getEnabledDomains(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('enabled', true)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDisabledDomains(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('enabled', false)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function enable(int $id): Domain
    {
        $domain = $this->findById($id);
        
        if (!$domain) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$id} not found");
        }
        
        $domain->update(['enabled' => true]);
        
        return $domain->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function disable(int $id): Domain
    {
        $domain = $this->findById($id);
        
        if (!$domain) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$id} not found");
        }
        
        $domain->update(['enabled' => false]);
        
        return $domain->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function assignUser(int $domainId, int $userId, string $role = 'domain_admin'): Domain
    {
        $domain = $this->findById($domainId);
        
        if (!$domain) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$domainId} not found");
        }
        
        $domain->administrators()->syncWithoutDetaching([$userId => ['role' => $role]]);
        
        return $domain->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeUser(int $domainId, int $userId): Domain
    {
        $domain = $this->findById($domainId);
        
        if (!$domain) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$domainId} not found");
        }
        
        $domain->administrators()->detach($userId);
        
        return $domain->fresh();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainUsers(int $domainId, array $relations = []): Collection
    {
        $domain = $this->findById($domainId);
        
        if (!$domain) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$domainId} not found");
        }
        
        return $domain->administrators()->with($relations)->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainAdministrators(int $domainId, array $relations = []): Collection
    {
        $domain = $this->findById($domainId);
        
        if (!$domain) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$domainId} not found");
        }
        
        return $domain->administrators()
            ->wherePivot('role', 'domain_admin')
            ->with($relations)
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function hasUser(int $domainId, int $userId): bool
    {
        $domain = $this->findById($domainId);
        
        if (!$domain) {
            return false;
        }
        
        return $domain->administrators()->where('user_id', $userId)->exists();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainsWithoutAdmins(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->doesntHave('administrators')
            ->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getDomainStatistics(int $domainId): array
    {
        $domain = $this->findById($domainId, ['eximUsers']);
        
        if (!$domain) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Domain with ID {$domainId} not found");
        }
        
        $totalUsers = $domain->eximUsers->count();
        $activeUsers = $domain->eximUsers->where('enabled', true)->count();
        $quotaUsage = 0;
        $quotaPercentage = 0;
        
        if ($domain->quotas > 0) {
            // This would need to be implemented based on my mail storage system
            // For now, return 0
            $quotaPercentage = ($quotaUsage / $domain->quotas) * 100;
        }
        
        return [
            'domain_id' => $domainId,
            'domain_name' => $domain->domain,
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'max_accounts' => $domain->max_accounts,
            'accounts_available' => $domain->max_accounts ? max(0, $domain->max_accounts - $totalUsers) : null,
            'quota_limit_mb' => $domain->quotas,
            'quota_usage_mb' => $quotaUsage,
            'quota_percentage' => round($quotaPercentage, 2),
            'enabled' => $domain->enabled,
            'type' => $domain->type,
            'has_spam_filtering' => (bool) $domain->spamassassin,
            'has_virus_scanning' => (bool) $domain->avscan,
            'has_blocklists' => (bool) $domain->blocklists,
            'administrator_count' => $domain->administrators->count(),
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkEnable(array $domainIds): int
    {
        return DB::transaction(function () use ($domainIds) {
            $count = 0;
            
            foreach ($domainIds as $domainId) {
                try {
                    $this->enable($domainId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    // Skip non-existent domains
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkDisable(array $domainIds): int
    {
        return DB::transaction(function () use ($domainIds) {
            $count = 0;
            
            foreach ($domainIds as $domainId) {
                try {
                    $this->disable($domainId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    // Skip non-existent domains
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $domainIds): int
    {
        return DB::transaction(function () use ($domainIds) {
            $count = 0;
            
            foreach ($domainIds as $domainId) {
                try {
                    $this->delete($domainId);
                    $count++;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    // Skip non-existent domains
                    continue;
                }
            }
            
            return $count;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function existsByDomainName(string $domainName, ?int $excludeId = null): bool
    {
        $query = $this->model->where('domain', $domainName);
        
        if ($excludeId !== null) {
            $query->where('domain_id', '!=', $excludeId);
        }
        
        return $query->exists();
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
    public function getDomainsWithSpamFiltering(array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('spamassassin', true)
            ->get();
    }
}