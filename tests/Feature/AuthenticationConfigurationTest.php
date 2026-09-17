<?php

use App\Auth\MultiTableUserProvider;
use Illuminate\Support\Facades\Auth;
use VEximweb\Core\Data\Repositories\EximUserRepository;
use VEximweb\Core\Data\Repositories\Interfaces\EximUserRepositoryInterface;
use VEximweb\Core\Data\Repositories\Interfaces\UserRepositoryInterface;
use VEximweb\Core\Data\Repositories\UserRepository;

it('uses the multi-table provider for the default web guard', function () {
    expect(config('auth.defaults.guard'))->toBe('web')
        ->and(config('auth.guards.web.provider'))->toBe('users')
        ->and(config('auth.providers.users.driver'))->toBe('multi_table')
        ->and(Auth::createUserProvider('users'))->toBeInstanceOf(MultiTableUserProvider::class);
});

it('binds both authentication repositories to their concrete implementations', function () {
    expect(app(UserRepositoryInterface::class))->toBeInstanceOf(UserRepository::class)
        ->and(app(EximUserRepositoryInterface::class))->toBeInstanceOf(EximUserRepository::class);
});
