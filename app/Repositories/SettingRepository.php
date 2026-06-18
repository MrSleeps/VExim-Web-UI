<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingRepository implements SettingRepositoryInterface
{
    /**
     * Cache key for all settings.
     */
    protected const CACHE_KEY = 'settings.all';
    
    /**
     * Cache duration in seconds (1 hour).
     */
    protected const CACHE_DURATION = 3600;
    
    /**
     * @var Setting
     */
    protected Setting $model;
    
    /**
     * SettingRepository constructor.
     *
     * @param Setting $model
     */
    public function __construct(Setting $model)
    {
        $this->model = $model;
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->getAll();
        return $settings[$key] ?? $default;
    }
    
    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value, string $type = null, string $description = null): Setting
    {
        // Auto-detect type if not specified
        if (!$type) {
            $type = match(true) {
                is_int($value) => 'integer',
                is_bool($value) => 'boolean',
                is_array($value) => 'json',
                default => 'string'
            };
        }

        $storedValue = match($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value
        };
        
        $setting = $this->model->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'description' => $description
            ]
        );
        
        $this->clearCache();
        
        return $setting;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            $settings = [];
            $records = $this->model->all();
            
            foreach ($records as $record) {
                $settings[$record->key] = $record->getTypedValue();
            }
            
            return $settings;
        });
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAllAsCollection(): Collection
    {
        return collect($this->getAll());
    }
    
    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $settings = $this->getAll();
        return array_key_exists($key, $settings);
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        $deleted = $this->model->where('key', $key)->delete() > 0;
        
        if ($deleted) {
            $this->clearCache();
        }
        
        return $deleted;
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys): int
    {
        $deleted = $this->model->whereIn('key', $keys)->delete();
        
        if ($deleted > 0) {
            $this->clearCache();
        }
        
        return $deleted;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }
    
    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $settings): int
    {
        $count = 0;
        
        foreach ($settings as $key => $value) {
            $description = null;
            $type = null;
            
            // Allow passing array with details
            if (is_array($value) && isset($value['value'])) {
                $type = $value['type'] ?? null;
                $description = $value['description'] ?? null;
                $value = $value['value'];
            }
            
            $this->set($key, $value, $type, $description);
            $count++;
        }
        
        return $count;
    }
    
    /**
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getModel(string $key): ?Setting
    {
        return $this->model->where('key', $key)->first();
    }
    
    /**
     * {@inheritdoc}
     */
    public function remember(string $key, callable $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->set($key, $value);
        
        return $value;
    }
    
    /**
     * Get all settings without using cache.
     *
     * @return array
     */
    public function getAllFresh(): array
    {
        $settings = [];
        $records = $this->model->all();
        
        foreach ($records as $record) {
            $settings[$record->key] = $record->getTypedValue();
        }
        
        return $settings;
    }
    
    /**
     * Bulk update settings with transaction support.
     *
     * @param array $settings
     * @param bool $deleteMissing If true, delete settings not in the provided array
     * @return array Updated settings
     */
    public function sync(array $settings, bool $deleteMissing = false): array
    {
        $result = [];
        
        \DB::transaction(function () use ($settings, $deleteMissing, &$result) {
            $updatedKeys = [];
            
            foreach ($settings as $key => $data) {
                $value = $data['value'] ?? $data;
                $type = $data['type'] ?? null;
                $description = $data['description'] ?? null;
                
                $setting = $this->set($key, $value, $type, $description);
                $result[$key] = $setting->getTypedValue();
                $updatedKeys[] = $key;
            }
            
            if ($deleteMissing) {
                $this->model->whereNotIn('key', $updatedKeys)->delete();
            }
            
            $this->clearCache();
        });
        
        return $result;
    }
    
    /**
     * Get settings by key pattern (LIKE query).
     *
     * @param string $pattern
     * @return Collection
     */
    public function getByPattern(string $pattern): Collection
    {
        $records = $this->model->where('key', 'LIKE', $pattern)->get();
        
        return $records->mapWithKeys(function ($record) {
            return [$record->key => $record->getTypedValue()];
        });
    }
}