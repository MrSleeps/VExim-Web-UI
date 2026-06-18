<?php

namespace App\Repositories\Interfaces;

use App\Models\DomainUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for DomainUser repository operations.
 * 
 * Defines the contract for all domain-user relationship data access operations,
 * including CRUD operations, search, and relationship management.
 */
interface DomainUserRepositoryInterface
{
    /**
     * Get all domain-user relationships.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function all(array $relations = []): Collection;
    
    /**
     * Get paginated domain-user relationships.
     * 
     * @param int $perPage Number of items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Find a domain-user relationship by ID.
     * 
     * @param int $id Relationship ID
     * @param array $relations Relationships to eager load
     * @return DomainUser|null
     */
    public function findById(int $id, array $relations = []): ?DomainUser;
    
    /**
     * Find a domain-user relationship by user and domain.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return DomainUser|null
     */
    public function findByUserAndDomain(int $userId, int $domainId, array $relations = []): ?DomainUser;
    
    /**
     * Create a new domain-user relationship.
     * 
     * @param array $data Relationship data (user_id, domain_id, role)
     * @return DomainUser
     * @throws \InvalidArgumentException If relationship already exists
     */
    public function create(array $data): DomainUser;
    
    /**
     * Update an existing domain-user relationship.
     * 
     * @param int $id Relationship ID
     * @param array $data Updated relationship data
     * @return DomainUser
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): DomainUser;
    
    /**
     * Delete a domain-user relationship.
     * 
     * @param int $id Relationship ID
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool;
    
    /**
     * Delete a domain-user relationship by user and domain.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @return bool
     */
    public function deleteByUserAndDomain(int $userId, int $domainId): bool;
    
    /**
     * Get all relationships for a specific user.
     * 
     * @param int $userId User ID
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getByUser(int $userId, array $relations = []): Collection;
    
    /**
     * Get all relationships for a specific domain.
     * 
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getByDomain(int $domainId, array $relations = []): Collection;
    
    /**
     * Get all domain administrators for a specific domain.
     * 
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainAdmins(int $domainId, array $relations = []): Collection;
    
    /**
     * Get all domains a user has access to.
     * 
     * @param int $userId User ID
     * @return Collection
     */
    public function getUserDomains(int $userId): Collection;
    
    /**
     * Get all users assigned to a domain.
     * 
     * @param int $domainId Domain ID
     * @return Collection
     */
    public function getDomainUsers(int $domainId): Collection;
    
    /**
     * Update or create a domain-user relationship.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @param string $role Role name
     * @return DomainUser
     */
    public function updateOrCreate(int $userId, int $domainId, string $role = 'domain_admin'): DomainUser;
    
    /**
     * Check if a user has access to a domain.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @return bool
     */
    public function userHasAccess(int $userId, int $domainId): bool;
    
    /**
     * Check if a user is an administrator of a domain.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @return bool
     */
    public function userIsDomainAdmin(int $userId, int $domainId): bool;
    
    /**
     * Get the role of a user for a specific domain.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @return string|null Role name or null if no relationship exists
     */
    public function getUserRoleForDomain(int $userId, int $domainId): ?string;
    
    /**
     * Bulk assign users to a domain with the same role.
     * 
     * @param int $domainId Domain ID
     * @param array $userIds Array of user IDs
     * @param string $role Role to assign (default: 'domain_admin')
     * @return int Number of assignments created
     */
    public function bulkAssignUsers(int $domainId, array $userIds, string $role = 'domain_admin'): int;
    
    /**
     * Bulk remove users from a domain.
     * 
     * @param int $domainId Domain ID
     * @param array $userIds Array of user IDs
     * @return int Number of assignments removed
     */
    public function bulkRemoveUsers(int $domainId, array $userIds): int;
    
    /**
     * Get all relationships with a specific role.
     * 
     * @param string $role Role name
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getByRole(string $role, array $relations = []): Collection;
    
    /**
     * Count relationships by domain.
     * 
     * @param int $domainId Domain ID
     * @return int
     */
    public function countByDomain(int $domainId): int;
    
    /**
     * Count relationships by user.
     * 
     * @param int $userId User ID
     * @return int
     */
    public function countByUser(int $userId): int;
    
    /**
     * Get domains with no user assignments.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainsWithoutAssignments(array $relations = []): Collection;
    
    /**
     * Get users with no domain assignments.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getUsersWithoutAssignments(array $relations = []): Collection;
    
    /**
     * Search domain-user relationships.
     * 
     * @param array $criteria Search criteria
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function search(array $criteria, array $relations = []): Collection;
}