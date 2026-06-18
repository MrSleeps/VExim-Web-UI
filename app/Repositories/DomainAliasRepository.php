<?php

namespace App\Repositories;

use App\Models\DomainAlias;
use App\Models\User;
use App\Repositories\Interfaces\DomainAliasRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DomainAliasRepository implements DomainAliasRepositoryInterface
{
    /**
     * @var DomainAlias
     */
    protected DomainAlias $model;
    
    /**
     * DomainAliasRepository constructor.
     *
     * @param DomainAlias $model
     */
    public function __construct(DomainAlias $model)
    {
        $this->model = $model;
    }
    
    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->get($columns);
    }
    
    /**
     * {@inheritdoc}
     */
    public function find(string $alias, array $columns = ['*']): ?DomainAlias
    {
        return $this->model->where('alias', $alias)->first($columns);
    }
    
    /**
     * {@inheritdoc}
     */
    public function findOrFail(string $alias, array $columns = ['*']): DomainAlias
    {
        return $this->model->where('alias', $alias)->firstOrFail($columns);
    }
    
    /**
     * {@inheritdoc}
     */
    public function create(array $data): DomainAlias
    {
        return $this->model->create($data);
    }
    
    /**
     * {@inheritdoc}
     */
    public function update(string $alias, array $data): bool
    {
        return $this->model->where('alias', $alias)->update($data) > 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(string $alias): bool
    {
        return $this->model->where('alias', $alias)->delete() > 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByDomainId(int $domainId): Collection
    {
        return $this->model->where('domain_id', $domainId)->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getForUser(User $user): Collection
    {
        $query = $this->model->newQuery();
        
        if ($user->isSystemAdmin()) {
            return $query->get();
        }
        
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('domain_id', $domainIds)->get();
        }

        return new Collection();
    }
    
    /**
     * {@inheritdoc}
     */
    public function exists(string $alias, ?int $domainId = null): bool
    {
        $query = $this->model->where('alias', $alias);
        
        if ($domainId !== null) {
            $query->where('domain_id', $domainId);
        }
        
        return $query->exists();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPrimaryDomain(string $alias): ?\App\Models\Domain
    {
        $domainAlias = $this->find($alias);
        
        if (!$domainAlias) {
            return null;
        }
        
        return $domainAlias->domain;
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteByDomainId(int $domainId): int
    {
        return $this->model->where('domain_id', $domainId)->delete();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByDomainIds(array $domainIds): Collection
    {
        return $this->model->whereIn('domain_id', $domainIds)->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function createMultiple(array $aliases): Collection
    {
        $created = [];
        
        foreach ($aliases as $aliasData) {
            $created[] = $this->create($aliasData);
        }
        
        return new Collection($created);
    }
    
    /**
     * Get all aliases with their domain relationships eager loaded.
     *
     * @return Collection
     */
    public function allWithDomain(): Collection
    {
        return $this->model->with('domain')->get();
    }
    
    /**
     * Find alias by alias name with domain relationship.
     *
     * @param string $alias
     * @return DomainAlias|null
     */
    public function findWithDomain(string $alias): ?DomainAlias
    {
        return $this->model->with('domain')->where('alias', $alias)->first();
    }
    
    /**
     * Check if a user can access a specific domain alias.
     *
     * @param string $alias
     * @param User $user
     * @return bool
     */
    public function userCanAccess(string $alias, User $user): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }
        
        if ($user->isDomainAdmin()) {
            $domainAlias = $this->find($alias);
            
            if (!$domainAlias) {
                return false;
            }
            
            return $user->domains()->where('domains.domain_id', $domainAlias->domain_id)->exists();
        }
        
        return false;
    }
    
    /**
     * Get paginated domain aliases for a user.
     *
     * @param User $user
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginateForUser(User $user, int $perPage = 15)
    {
        $query = $this->model->newQuery();
        
        if ($user->isSystemAdmin()) {
            return $query->paginate($perPage);
        }
        
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('domain_id', $domainIds)->paginate($perPage);
        }
        
        // Return empty paginator for unauthorized users
        return $query->whereRaw('1 = 0')->paginate($perPage);
    }
    
    /**
     * Get alias count for a specific domain.
     *
     * @param int $domainId
     * @return int
     */
    public function countByDomainId(int $domainId): int
    {
        return $this->model->where('domain_id', $domainId)->count();
    }
    
    /**
     * Get aliases by alias name pattern (LIKE query).
     *
     * @param string $pattern
     * @return Collection
     */
    public function getByPattern(string $pattern): Collection
    {
        return $this->model->where('alias', 'LIKE', $pattern)->get();
    }
    
    /**
     * Update or create a domain alias.
     *
     * @param string $alias
     * @param array $data
     * @return DomainAlias
     */
    public function updateOrCreate(string $alias, array $data): DomainAlias
    {
        return $this->model->updateOrCreate(
            ['alias' => $alias],
            $data
        );
    }
}