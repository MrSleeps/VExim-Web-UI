<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

/**
 * Interface for Setting repository operations.
 * 
 * Defines the contract for managing application settings with caching support.
 */
interface SettingRepositoryInterface
{
    /**
     * Get a setting value by its key.
     *
     * @param string $key The setting key to retrieve
     * @param mixed $default Default value to return if setting doesn't exist
     * @return mixed The setting value with proper type casting
     */
    public function get(string $key, $default = null);
    
    /**
     * Store or update a setting value.
     *
     * @param string $key The setting key to store
     * @param mixed $value The value to store
     * @param string|null $type Optional data type override
     * @param string|null $description Optional description of the setting
     * @return \App\Models\Setting
     */
    public function set(string $key, $value, string $type = null, string $description = null): \App\Models\Setting;
    
    /**
     * Get all settings with proper type casting.
     *
     * @return array Associative array of all settings [key => typed_value]
     */
    public function getAll(): array;
    
    /**
     * Get all settings as a collection.
     *
     * @return Collection
     */
    public function getAllAsCollection(): Collection;
    
    /**
     * Check if a setting exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * Delete a setting by its key.
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;
    
    /**
     * Delete multiple settings by their keys.
     *
     * @param array $keys
     * @return int Number of deleted records
     */
    public function deleteMultiple(array $keys): int;
    
    /**
     * Get settings by their type.
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection;
    
    /**
     * Update multiple settings at once.
     *
     * @param array $settings Associative array [key => value]
     * @return int Number of updated/created settings
     */
    public function setMultiple(array $settings): int;
    
    /**
     * Clear the settings cache.
     *
     * @return void
     */
    public function clearCache(): void;
    
    /**
     * Get the underlying model instance.
     *
     * @param string $key
     * @return \App\Models\Setting|null
     */
    public function getModel(string $key): ?\App\Models\Setting;
    
    /**
     * Get or set a setting with a callback for missing values.
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, callable $callback);
}