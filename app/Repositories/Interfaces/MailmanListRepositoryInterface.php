<?php

namespace App\Repositories\Interfaces;

use App\Models\MailmanList;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface MailmanListRepositoryInterface
{
    /**
     * Get all mailing lists.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, MailmanList>
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated mailing lists.
     *
     * @param int $perPage
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find a mailing list by its ID.
     *
     * @param int $listId
     * @return MailmanList|null
     */
    public function findById(int $listId): ?MailmanList;

    /**
     * Find a mailing list by its full email address.
     *
     * @param string $email
     * @return MailmanList|null
     */
    public function findByEmail(string $email): ?MailmanList;

    /**
     * Find a mailing list by Mailman's internal list ID.
     *
     * @param string $mailmanListId
     * @return MailmanList|null
     */
    public function findByMailmanId(string $mailmanListId): ?MailmanList;

    /**
     * Get all mailing lists for a specific domain.
     *
     * @param int $domainId
     * @param bool $onlyEnabled
     * @return Collection<int, MailmanList>
     */
    public function getByDomain(int $domainId, bool $onlyEnabled = false): Collection;

    /**
     * Create a new mailing list.
     *
     * @param array<string, mixed> $data
     * @return MailmanList
     */
    public function create(array $data): MailmanList;

    /**
     * Update an existing mailing list.
     *
     * @param int $listId
     * @param array<string, mixed> $data
     * @return MailmanList
     */
    public function update(int $listId, array $data): MailmanList;

    /**
     * Delete a mailing list (soft delete).
     *
     * @param int $listId
     * @return bool
     */
    public function delete(int $listId): bool;

    /**
     * Force delete a mailing list (permanent).
     *
     * @param int $listId
     * @return bool
     */
    public function forceDelete(int $listId): bool;

    /**
     * Restore a soft-deleted mailing list.
     *
     * @param int $listId
     * @return bool
     */
    public function restore(int $listId): bool;

    /**
     * Enable a mailing list.
     *
     * @param int $listId
     * @return bool
     */
    public function enable(int $listId): bool;

    /**
     * Disable a mailing list.
     *
     * @param int $listId
     * @return bool
     */
    public function disable(int $listId): bool;

    /**
     * Check if a mailing list exists for a given email.
     *
     * @param string $email
     * @return bool
     */
    public function exists(string $email): bool;

    /**
     * Count mailing lists for a domain.
     *
     * @param int $domainId
     * @param bool $onlyEnabled
     * @return int
     */
    public function countByDomain(int $domainId, bool $onlyEnabled = false): int;

    /**
     * Sync lists with Mailman 3 API.
     *
     * @param int $domainId
     * @param array<string, mixed> $mailmanLists
     * @return array<string, mixed>
     */
    public function syncWithMailman(int $domainId, array $mailmanLists): array;

    /**
     * Get lists for Exim router lookup.
     *
     * @param string $email
     * @return array<string, mixed>|null
     */
    public function getForEximRouter(string $email): ?array;
}