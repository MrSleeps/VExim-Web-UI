<?php

namespace App\Repositories\Interfaces;

use App\Models\EximUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Interface for EximUser repository operations.
 * 
 * Defines the contract for all email user data access operations,
 * including CRUD operations, search, filtering, and domain management.
 */
interface EximUserRepositoryInterface
{
    /**
     * Get all email users.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function all(array $relations = []): Collection;
    
    /**
     * Get paginated email users.
     * 
     * @param int $perPage Number of items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Find an email user by ID.
     * 
     * @param int $id User ID
     * @param array $relations Relationships to eager load
     * @return EximUser|null
     */
    public function findById(int $id, array $relations = []): ?EximUser;
    
    /**
     * Find an email user by username (full email address).
     * 
     * @param string $username Full email address (localpart@domain)
     * @param array $relations Relationships to eager load
     * @return EximUser|null
     */
    public function findByUsername(string $username, array $relations = []): ?EximUser;
    
    /**
     * Find email users by domain ID.
     * 
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function findByDomainId(int $domainId, array $relations = []): Collection;
    
    /**
     * Find email users by local part and domain.
     * 
     * @param string $localpart Local part of email address
     * @param int $domainId Domain ID
     * @param array $relations Relationships to eager load
     * @return EximUser|null
     */
    public function findByLocalpartAndDomain(string $localpart, int $domainId, array $relations = []): ?EximUser;
    
    /**
     * Create a new email user.
     * 
     * @param array $data User data
     * @return EximUser
     */
    public function create(array $data): EximUser;
    
    /**
     * Update an existing email user.
     * 
     * @param int $id User ID
     * @param array $data Updated user data
     * @return EximUser
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): EximUser;
    
    /**
     * Delete an email user.
     * 
     * @param int $id User ID
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool;
    
    /**
     * Enable an email user.
     * 
     * @param int $id User ID
     * @return EximUser
     */
    public function enable(int $id): EximUser;
    
    /**
     * Disable an email user.
     * 
     * @param int $id User ID
     * @return EximUser
     */
    public function disable(int $id): EximUser;
    
    /**
     * Get only enabled email users.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getEnabledUsers(array $relations = []): Collection;
    
    /**
     * Get only disabled email users.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getDisabledUsers(array $relations = []): Collection;
    
    /**
     * Get users by type.
     * 
     * @param string $type User type (local, forward, alias)
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getByType(string $type, array $relations = []): Collection;
    
    /**
     * Search email users by various criteria.
     * 
     * @param array $criteria Search criteria (username, localpart, email, etc.)
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function search(array $criteria, array $relations = []): Collection;
    
    /**
     * Search email users with pagination.
     * 
     * @param array $criteria Search criteria
     * @param int $perPage Items per page
     * @param array $relations Relationships to eager load
     * @return LengthAwarePaginator
     */
    public function searchPaginated(array $criteria, int $perPage = 15, array $relations = []): LengthAwarePaginator;
    
    /**
     * Get users with spam filtering enabled.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getWithSpamFiltering(array $relations = []): Collection;
    
    /**
     * Get users with forwarding enabled.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getWithForwarding(array $relations = []): Collection;
    
    /**
     * Get users with vacation auto-responder enabled.
     * 
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getWithVacation(array $relations = []): Collection;
    
    /**
     * Update user password.
     * 
     * @param int $id User ID
     * @param string $password New password (will be hashed)
     * @return EximUser
     */
    public function updatePassword(int $id, string $password): EximUser;
    
    /**
     * Update user quota.
     * 
     * @param int $id User ID
     * @param int $quota Quota in MB
     * @return EximUser
     */
    public function updateQuota(int $id, int $quota): EximUser;
    
    /**
     * Get quota usage for a user.
     * 
     * @param int $id User ID
     * @return array Quota usage information
     */
    public function getQuotaUsage(int $id): array;
    
    /**
     * Bulk enable users.
     * 
     * @param array $userIds Array of user IDs
     * @return int Number of enabled users
     */
    public function bulkEnable(array $userIds): int;
    
    /**
     * Bulk disable users.
     * 
     * @param array $userIds Array of user IDs
     * @return int Number of disabled users
     */
    public function bulkDisable(array $userIds): int;
    
    /**
     * Bulk delete users.
     * 
     * @param array $userIds Array of user IDs
     * @return int Number of deleted users
     */
    public function bulkDelete(array $userIds): int;
    
    /**
     * Check if a username exists.
     * 
     * @param string $username Full email address
     * @param int|null $excludeId User ID to exclude from check
     * @return bool
     */
    public function existsByUsername(string $username, ?int $excludeId = null): bool;
    
    /**
     * Get users by domain with statistics.
     * 
     * @param int $domainId Domain ID
     * @return array Domain user statistics
     */
    public function getDomainUserStatistics(int $domainId): array;
    
    /**
     * Get users nearing their quota limit.
     * 
     * @param int $thresholdPercentage Percentage threshold (e.g., 90 for 90%)
     * @param array $relations Relationships to eager load
     * @return Collection
     */
    public function getUsersNearQuotaLimit(int $thresholdPercentage = 90, array $relations = []): Collection;
	
    /**
     * Get only enabled (active) users for authentication
     * 
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEnabledForAuth(array $relations = []): Collection;
    
    /**
     * Find a user by username that is enabled (for authentication)
     * 
     * @param string $username
     * @param array $relations
     * @return \App\Models\EximUser|null
     */
    public function findEnabledByUsername(string $username, array $relations = []): ?EximUser;
    
    /**
     * Find a user by ID that is enabled (for authentication)
     * 
     * @param int $id
     * @param array $relations
     * @return \App\Models\EximUser|null
     */
    public function findEnabledById(int $id, array $relations = []): ?EximUser;
	
    /**
     * Find an exim user by remember token that is enabled
     * 
     * @param string $token
     * @param array $relations
     * @return \App\Models\EximUser|null
     */
    public function findEnabledByRememberToken(string $token, array $relations = []): ?EximUser;	
    
    /**
     * Get users with their spam threshold settings for Rspamd
     * 
     * @param array $relations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersWithSpamSettings(array $relations = []): Collection;    
}