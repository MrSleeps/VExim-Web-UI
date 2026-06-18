<?php

namespace App\Repositories\Interfaces;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for Domain repository operations.
 * 
 * Defines the contract for all domain data access operations,
 * including CRUD operations, search capabilities, and user assignments.
 */
interface DomainRepositoryInterface
{
    /**
     * Get all domains.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function all(array $relations = []): Collection;
    
    /**
     * Get paginated domains.
     * 
     * @param int $perPage Number of items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Find a domain by ID.
     * 
     * @param int $id Domain ID
     * @param array $relations Relationships to eager load
     * @return Domain|null
     */
    public function findById(int $id, array $relations = []): ?Domain;
    
    /**
     * Find a domain by name.
     * 
     * @param string $domainName Domain name (e.g., 'example.com')
     * @param array $relations Relationships to eager load
     * @return Domain|null
     */
    public function findByDomainName(string $domainName, array $relations = []): ?Domain;
    
    /**
     * Create a new domain.
     * 
     * @param array $data Domain data
     * @return Domain
     */
    public function create(array $data): Domain;
    
    /**
     * Update an existing domain.
     * 
     * @param int $id Domain ID
     * @param array $data Updated domain data
     * @return Domain
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): Domain;
    
    /**
     * Delete a domain.
     * 
     * @param int $id Domain ID
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool;
    
    /**
     * Search domains by various criteria.
     * 
     * @param array $criteria Search criteria (domain, type, enabled, etc.)
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function search(array $criteria, array $relations = []): Collection;
    
    /**
     * Search domains with pagination.
     * 
     * @param array $criteria Search criteria
     * @param int $perPage Items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function searchPaginated(array $criteria, int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Get only enabled domains.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getEnabledDomains(array $relations = []): Collection;
    
    /**
     * Get only disabled domains.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDisabledDomains(array $relations = []): Collection;
    
    /**
     * Enable a domain.
     * 
     * @param int $id Domain ID
     * @return Domain
     */
    public function enable(int $id): Domain;
    
    /**
     * Disable a domain.
     * 
     * @param int $id Domain ID
     * @return Domain
     */
    public function disable(int $id): Domain;
    
    /**
     * Assign a user (administrator) to a domain.
     * 
     * @param int $domainId Domain ID
     * @param int $userId User ID
     * @param string $role Role within the domain (default: 'domain_admin')
     * @return Domain
     */
    public function assignUser(int $domainId, int $userId, string $role = 'domain_admin'): Domain;
    
    /**
     * Remove a user from a domain.
     * 
     * @param int $domainId Domain ID
     * @param int $userId User ID
     * @return Domain
     */
    public function removeUser(int $domainId, int $userId): Domain;
    
    /**
     * Get all users assigned to a domain.
     * 
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainUsers(int $domainId, array $relations = []): Collection;
    
    /**
     * Get all administrators for a domain.
     * 
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainAdministrators(int $domainId, array $relations = []): Collection;
    
    /**
     * Check if a domain has a specific user assigned.
     * 
     * @param int $domainId Domain ID
     * @param int $userId User ID
     * @return bool
     */
    public function hasUser(int $domainId, int $userId): bool;
    
    /**
     * Get domains with no administrators assigned.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainsWithoutAdmins(array $relations = []): Collection;
    
    /**
     * Get domain statistics.
     * 
     * @param int $domainId Domain ID
     * @return array Statistics including user count, quota usage, etc.
     */
    public function getDomainStatistics(int $domainId): array;
    
    /**
     * Bulk enable domains.
     * 
     * @param array $domainIds Array of domain IDs
     * @return int Number of enabled domains
     */
    public function bulkEnable(array $domainIds): int;
    
    /**
     * Bulk disable domains.
     * 
     * @param array $domainIds Array of domain IDs
     * @return int Number of disabled domains
     */
    public function bulkDisable(array $domainIds): int;
    
    /**
     * Bulk delete domains.
     * 
     * @param array $domainIds Array of domain IDs
     * @return int Number of deleted domains
     */
    public function bulkDelete(array $domainIds): int;
    
    /**
     * Check if a domain name exists.
     * 
     * @param string $domainName Domain name
     * @param int|null $excludeId Domain ID to exclude from check
     * @return bool
     */
    public function existsByDomainName(string $domainName, ?int $excludeId = null): bool;
    
    /**
     * Get domains by type.
     * 
     * @param string $type Domain type (local, alias, remote)
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getByType(string $type, array $relations = []): Collection;
    
    /**
     * Get domains with spam filtering enabled.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainsWithSpamFiltering(array $relations = []): Collection;
}