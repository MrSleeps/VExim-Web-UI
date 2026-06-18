<?php

namespace App\Repositories;

use App\Models\MailmanList;
use App\Repositories\Interfaces\MailmanListRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MailmanListRepository implements MailmanListRepositoryInterface
{
    /**
     * @var MailmanList
     */
    protected MailmanList $model;

    /**
     * MailmanListRepository constructor.
     *
     * @param MailmanList $model
     */
    public function __construct(MailmanList $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $listId): ?MailmanList
    {
        return $this->model->find($listId);
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?MailmanList
    {
        return $this->model
            ->byEmail($email)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByMailmanId(string $mailmanListId): ?MailmanList
    {
        return $this->model
            ->where('mailman_list_id', $mailmanListId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByDomain(int $domainId, bool $onlyEnabled = false): Collection
    {
        $query = $this->model->forDomain($domainId);

        if ($onlyEnabled) {
            $query->enabled();
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): MailmanList
    {
        $list = $this->model->create($data);
        
        Log::info('Mailman list created', [
            'list_id' => $list->list_id,
            'list_email' => $list->list_email,
            'domain_id' => $list->domain_id
        ]);

        return $list;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $listId, array $data): MailmanList
    {
        $list = $this->findOrFail($listId);
        $list->update($data);

        Log::info('Mailman list updated', [
            'list_id' => $list->list_id,
            'list_email' => $list->list_email
        ]);

        return $list->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $listId): bool
    {
        $list = $this->findOrFail($listId);
        $deleted = $list->delete();

        Log::info('Mailman list deleted (soft)', [
            'list_id' => $list->list_id,
            'list_email' => $list->list_email
        ]);

        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(int $listId): bool
    {
        $list = $this->findOrFail($listId);
        $deleted = $list->forceDelete();

        Log::info('Mailman list force deleted (permanent)', [
            'list_id' => $list->list_id,
            'list_email' => $list->list_email
        ]);

        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function restore(int $listId): bool
    {
        $list = $this->model
            ->withTrashed()
            ->findOrFail($listId);
        
        $restored = $list->restore();

        Log::info('Mailman list restored', [
            'list_id' => $list->list_id,
            'list_email' => $list->list_email
        ]);

        return $restored;
    }

    /**
     * {@inheritDoc}
     */
    public function enable(int $listId): bool
    {
        $list = $this->findOrFail($listId);
        return $list->update(['enabled' => true]);
    }

    /**
     * {@inheritDoc}
     */
    public function disable(int $listId): bool
    {
        $list = $this->findOrFail($listId);
        return $list->update(['enabled' => false]);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $email): bool
    {
        return $this->model
            ->byEmail($email)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function countByDomain(int $domainId, bool $onlyEnabled = false): int
    {
        $query = $this->model->forDomain($domainId);

        if ($onlyEnabled) {
            $query->enabled();
        }

        return $query->count();
    }

    /**
     * {@inheritDoc}
     */
    public function syncWithMailman(int $domainId, array $mailmanLists): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => 0,
        ];

        try {
            DB::transaction(function () use ($domainId, $mailmanLists, &$results) {
                // Get current lists for this domain
                $currentLists = $this->getByDomain($domainId);
                $currentEmails = $currentLists->pluck('list_email')->toArray();

                // Get emails from Mailman
                $mailmanEmails = array_column($mailmanLists, 'list_email');

                // Lists to delete (in our DB but not in Mailman)
                $toDelete = array_diff($currentEmails, $mailmanEmails);
                foreach ($toDelete as $email) {
                    $list = $this->findByEmail($email);
                    if ($list && $this->delete($list->list_id)) {
                        $results['deleted']++;
                    } else {
                        $results['errors']++;
                    }
                }

                // Create or update lists
                foreach ($mailmanLists as $mailmanList) {
                    $existing = $this->findByEmail($mailmanList['list_email']);
                    
                    if ($existing) {
                        // Update existing
                        $this->update($existing->list_id, [
                            'list_name' => $mailmanList['list_name'],
                            'mailman_list_id' => $mailmanList['mailman_list_id'],
                            'enabled' => $mailmanList['enabled'] ?? true,
                        ]);
                        $results['updated']++;
                    } else {
                        // Create new
                        $this->create([
                            'domain_id' => $domainId,
                            'list_name' => $mailmanList['list_name'],
                            'list_email' => $mailmanList['list_email'],
                            'mailman_list_id' => $mailmanList['mailman_list_id'],
                            'enabled' => $mailmanList['enabled'] ?? true,
                        ]);
                        $results['created']++;
                    }
                }
            });

            Log::info('Mailman sync completed', [
                'domain_id' => $domainId,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Mailman sync failed', [
                'domain_id' => $domainId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $results['errors']++;
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function getForEximRouter(string $email): ?array
    {
        $list = $this->model
            ->byEmail($email)
            ->enabled()
            ->first();

        if (!$list) {
            return null;
        }

        return [
            'list_id' => $list->list_id,
            'list_email' => $list->list_email,
            'mailman_list_id' => $list->mailman_list_id,
            'domain_id' => $list->domain_id,
        ];
    }

    /**
     * Find a list or fail.
     *
     * @param int $listId
     * @return MailmanList
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function findOrFail(int $listId): MailmanList
    {
        return $this->model->findOrFail($listId);
    }

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, mixed> $filters
     * @return void
     */
    protected function applyFilters($query, array $filters): void
    {
        if (isset($filters['domain_id'])) {
            $query->forDomain($filters['domain_id']);
        }

        if (isset($filters['enabled']) && is_bool($filters['enabled'])) {
            if ($filters['enabled']) {
                $query->enabled();
            } else {
                $query->where('enabled', false);
            }
        }

        if (isset($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('list_name', 'like', $search)
                  ->orWhere('list_email', 'like', $search)
                  ->orWhere('mailman_list_id', 'like', $search);
            });
        }

        if (isset($filters['with_domain']) && $filters['with_domain'] === true) {
            $query->with('domain');
        }

        if (isset($filters['order_by']) && isset($filters['order_direction'])) {
            $query->orderBy($filters['order_by'], $filters['order_direction']);
        }

        if (isset($filters['with_trashed']) && $filters['with_trashed'] === true) {
            $query->withTrashed();
        }

        if (isset($filters['only_trashed']) && $filters['only_trashed'] === true) {
            $query->onlyTrashed();
        }
    }
}