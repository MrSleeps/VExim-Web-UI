<?php

use App\Auth\MultiTableUserProvider;
use Illuminate\Support\Facades\Hash;
use VEximweb\Core\Data\Models\EximUser;
use VEximweb\Core\Data\Models\User;
use VEximweb\Core\Data\Repositories\Interfaces\EximUserRepositoryInterface;
use VEximweb\Core\Data\Repositories\Interfaces\UserRepositoryInterface;

beforeEach(function () {
    $this->webUsers = Mockery::mock(UserRepositoryInterface::class);
    $this->mailUsers = Mockery::mock(EximUserRepositoryInterface::class);
    $this->provider = new MultiTableUserProvider($this->webUsers, $this->mailUsers);
});

it('prefers an active web user when retrieving by id', function () {
    $user = new User;
    $user->forceFill(['id' => 42, 'active' => true]);

    $this->webUsers
        ->shouldReceive('findActiveById')
        ->once()
        ->with(42)
        ->andReturn($user);

    $this->mailUsers->shouldNotReceive('findEnabledById');

    expect($this->provider->retrieveById(42))->toBe($user);
});

it('falls back to an enabled mailbox user when no web user matches the id', function () {
    $user = new EximUser;
    $user->forceFill(['user_id' => 42, 'enabled' => true]);

    $this->webUsers
        ->shouldReceive('findActiveById')
        ->once()
        ->with(42)
        ->andReturnNull();

    $this->mailUsers
        ->shouldReceive('findEnabledById')
        ->once()
        ->with(42)
        ->andReturn($user);

    expect($this->provider->retrieveById(42))->toBe($user);
});

it('returns null when neither account type is active for the id', function () {
    $this->webUsers
        ->shouldReceive('findActiveById')
        ->once()
        ->with(42)
        ->andReturnNull();

    $this->mailUsers
        ->shouldReceive('findEnabledById')
        ->once()
        ->with(42)
        ->andReturnNull();

    expect($this->provider->retrieveById(42))->toBeNull();
});

it('does not query repositories when credentials contain no identity', function () {
    $this->webUsers->shouldNotReceive('findActiveByEmail');
    $this->mailUsers->shouldNotReceive('findEnabledByUsername');

    expect($this->provider->retrieveByCredentials(['password' => 'secret']))->toBeNull();
});

it('looks up active web users by email before checking mailbox accounts', function () {
    $user = new User;
    $user->forceFill([
        'id' => 7,
        'email' => 'admin@example.test',
        'active' => true,
    ]);

    $this->webUsers
        ->shouldReceive('findActiveByEmail')
        ->once()
        ->with('admin@example.test')
        ->andReturn($user);

    $this->mailUsers->shouldNotReceive('findEnabledByUsername');

    expect($this->provider->retrieveByCredentials([
        'email' => 'admin@example.test',
        'password' => 'secret',
    ]))->toBe($user);
});

it('rejects inactive web users even when their password is correct', function () {
    $user = new User;
    $user->forceFill([
        'active' => false,
        'password' => Hash::make('secret'),
    ]);

    expect($this->provider->validateCredentials($user, ['password' => 'secret']))->toBeFalse();
});

it('validates passwords for active web users', function () {
    $user = new User;
    $user->forceFill([
        'active' => true,
        'password' => Hash::make('secret'),
    ]);

    expect($this->provider->validateCredentials($user, ['password' => 'secret']))->toBeTrue()
        ->and($this->provider->validateCredentials($user, ['password' => 'wrong']))->toBeFalse();
});
