<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for User repository operations.
 * 
 * Defines the contract for all user data access operations,
 * including CRUD operations, search capabilities, and specialized
 * queries for role-based access control.
 */
interface UserRepositoryInterface
{
    /**
     * Get all users.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function all(array $relations = []): Collection;
    
    /**
     * Get paginated users.
     * 
     * @param int $perPage Number of items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Find a user by ID.
     * 
     * @param int $id User ID
     * @param array $relations Relationships to eager load
     * @return User|null
     */
    public function findById(int $id, array $relations = []): ?User;
    
    /**
     * Find a user by email.
     * 
     * @param string $email User email address
     * @param array $relations Relationships to eager load
     * @return User|null
     */
    public function findByEmail(string $email, array $relations = []): ?User;
    
    /**
     * Create a new user.
     * 
     * @param array $data User data (name, email, password)
     * @return User
     */
    public function create(array $data): User;
    
    /**
     * Update an existing user.
     * 
     * @param int $id User ID
     * @param array $data Updated user data
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): User;
    
    /**
     * Delete a user.
     * 
     * @param int $id User ID
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool;
    
    /**
     * Search users by various criteria.
     * 
     * @param array $criteria Search criteria (name, email, role, etc.)
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function search(array $criteria, array $relations = []): Collection;
    
    /**
     * Search users with pagination.
     * 
     * @param array $criteria Search criteria
     * @param int $perPage Items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function searchPaginated(array $criteria, int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Get users by role.
     * 
     * @param string $role Role name (system_admin, domain_admin, domain_user)
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getByRole(string $role, array $relations = []): Collection;
    
    /**
     * Get users assigned to a specific domain.
     * 
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getByDomain(int $domainId, array $relations = []): Collection;
    
    /**
     * Get system administrators.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getSystemAdmins(array $relations = []): Collection;
    
    /**
     * Get domain administrators.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainAdmins(array $relations = []): Collection;
    
    /**
     * Get domain users (regular users).
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDomainUsers(array $relations = []): Collection;
    
    /**
     * Assign a role to a user.
     * 
     * @param int $userId User ID
     * @param string $role Role name
     * @return User
     */
    public function assignRole(int $userId, string $role): User;
    
    /**
     * Assign a domain to a user with a specific role.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @param string $role Role within the domain
     * @return User
     */
    public function assignDomain(int $userId, int $domainId, string $role = 'viewer'): User;
    
    /**
     * Remove a domain from a user.
     * 
     * @param int $userId User ID
     * @param int $domainId Domain ID
     * @return User
     */
    public function removeDomain(int $userId, int $domainId): User;
    
    /**
     * Get users with their domain assignments.
     * 
     * @param array $relations Additional relationships to load
     * @return Collection
     */
    public function getWithDomains(array $relations = []): Collection;
    
    /**
     * Count users by role.
     * 
     * @return array Associative array with role counts
     */
    public function countByRole(): array;
    
    /**
     * Check if a user exists by email.
     * 
     * @param string $email Email address to check
     * @param int|null $excludeId User ID to exclude from check
     * @return bool
     */
    public function existsByEmail(string $email, ?int $excludeId = null): bool;
    
    /**
     * Bulk delete users.
     * 
     * @param array $userIds Array of user IDs
     * @return int Number of deleted users
     */
    public function bulkDelete(array $userIds): int;
    
    /**
     * Update user's last login timestamp.
     * 
     * @param int $userId User ID
     * @return User
     */
    public function updateLastLogin(int $userId): User;
    
    /**
     * Activate a user.
     * 
     * @param int $id User ID
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function activate(int $id): User;
    
    /**
     * Deactivate a user.
     * 
     * @param int $id User ID
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deactivate(int $id): User;
    
    /**
     * Toggle user active status.
     * 
     * @param int $id User ID
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function toggleActive(int $id): User;
    
    /**
     * Get only active users.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getActiveUsers(array $relations = []): Collection;
    
    /**
     * Get only inactive users.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getInactiveUsers(array $relations = []): Collection;
    
    /**
     * Bulk activate users.
     * 
     * @param array $userIds Array of user IDs
     * @return int Number of activated users
     */
    public function bulkActivate(array $userIds): int;
    
    /**
     * Bulk deactivate users.
     * 
     * @param array $userIds Array of user IDs
     * @return int Number of deactivated users
     */
    public function bulkDeactivate(array $userIds): int;
    
    /**
     * Get paginated active users.
     * 
     * @param int $perPage Items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function getActiveUsersPaginated(int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Get paginated inactive users.
     * 
     * @param int $perPage Items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function getInactiveUsersPaginated(int $perPage = 15, array $relations = []): LengthAwarePaginator;    
	
    /**
     * Get only active users for authentication
     * 
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveForAuth(array $relations = []): Collection;
    
    /**
     * Find a user by email that is active (for authentication)
     * 
     * @param string $email
     * @param array $relations
     * @return \App\Models\User|null
     */
    public function findActiveByEmail(string $email, array $relations = []): ?User;
    
    /**
     * Find a user by ID that is active (for authentication)
     * 
     * @param int $id
     * @param array $relations
     * @return \App\Models\User|null
     */
    public function findActiveById(int $id, array $relations = []): ?User;
	
    /**
     * Find a user by remember token that is active
     * 
     * @param string $token
     * @param array $relations
     * @return \App\Models\User|null
     */
    public function findActiveByRememberToken(string $token, array $relations = []): ?User;	
}