<?php

use Illuminate\Support\Facades\Schema;
use Laravel\Passkeys\Passkeys;
use VEximweb\Core\Data\Models\User;

beforeEach(function () {
    Schema::dropIfExists('passkeys');
    Schema::dropIfExists('vw_sessions');
    Schema::dropIfExists('vw_password_reset_tokens');
    Schema::dropIfExists('users_web');
});

afterEach(function () {
    Schema::dropIfExists('passkeys');
    Schema::dropIfExists('vw_sessions');
    Schema::dropIfExists('vw_password_reset_tokens');
    Schema::dropIfExists('users_web');
});

it('runs the locked passkeys migration against the core web user model', function () {
    $coreMigrations = base_path('vendor/mrsleeps/vexim-web-core-data/database/migrations');

    (require $coreMigrations.'/0001_01_01_000000_create_users_web_table.php')->up();

    expect(Passkeys::userModel())->toBe(User::class)
        ->and(Schema::hasTable('users_web'))->toBeTrue();

    (require $coreMigrations.'/2026_06_16_133952_create_passkeys_table.php')->up();

    expect(Schema::hasTable('passkeys'))->toBeTrue();
});
