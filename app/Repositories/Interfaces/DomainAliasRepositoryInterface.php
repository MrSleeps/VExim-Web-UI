<?php

namespace App\Repositories\Interfaces;

use App\Models\DomainAlias;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for DomainAlias repository operations.
 * 
 * Defines the contract for managing domain aliases with user access control.
 */
interface DomainAliasRepositoryInterface
{
    /**
     * Get all domain aliases.
     *
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection;
    
    /**
     * Find a domain alias by its alias name.
     *
     * @param string $alias
     * @param array $columns
     * @return DomainAlias|null
     */
    public function find(string $alias, array $columns = ['*']): ?DomainAlias;
    
    /**
     * Find a domain alias by its alias name or fail.
     *
     * @param string $alias
     * @param array $columns
     * @return DomainAlias
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $alias, array $columns = ['*']): DomainAlias;
    
    /**
     * Create a new domain alias.
     *
     * @param array $data
     * @return DomainAlias
     */
    public function create(array $data): DomainAlias;
    
    /**
     * Update a domain alias.
     *
     * @param string $alias
     * @param array $data
     * @return bool
     */
    public function update(string $alias, array $data): bool;
    
    /**
     * Delete a domain alias.
     *
     * @param string $alias
     * @return bool
     */
    public function delete(string $alias): bool;
    
    /**
     * Get domain aliases for a specific domain.
     *
     * @param int $domainId
     * @return Collection
     */
    public function getByDomainId(int $domainId): Collection;
    
    /**
     * Get domain aliases accessible by a specific user.
     *
     * @param User $user
     * @return Collection
     */
    public function getForUser(User $user): Collection;
    
    /**
     * Check if an alias exists for a domain.
     *
     * @param string $alias
     * @param int|null $domainId
     * @return bool
     */
    public function exists(string $alias, ?int $domainId = null): bool;
    
    /**
     * Get the primary domain for an alias.
     *
     * @param string $alias
     * @return \App\Models\Domain|null
     */
    public function getPrimaryDomain(string $alias): ?\App\Models\Domain;
    
    /**
     * Delete all aliases for a specific domain.
     *
     * @param int $domainId
     * @return int Number of deleted records
     */
    public function deleteByDomainId(int $domainId): int;
    
    /**
     * Get aliases by multiple domain IDs.
     *
     * @param array $domainIds
     * @return Collection
     */
    public function getByDomainIds(array $domainIds): Collection;
    
    /**
     * Create multiple domain aliases at once.
     *
     * @param array $aliases Array of alias data [['alias' => 'example.com', 'domain_id' => 1], ...]
     * @return Collection
     */
    public function createMultiple(array $aliases): Collection;
}