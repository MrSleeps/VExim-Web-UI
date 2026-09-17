<?php

use App\Filament\Resources\DomainUsers\DomainUserResource;
use Illuminate\Support\Facades\Auth;
use VEximweb\Core\Data\Models\EximUser;

function eximUserWithId(int $id): EximUser
{
    return (new EximUser)->forceFill([
        'user_id' => $id,
        'username' => "user{$id}@example.test",
    ]);
}

it('scopes the resource query to the authenticated mailbox', function () {
    $user = eximUserWithId(42);
    Auth::setUser($user);

    $query = DomainUserResource::getEloquentQuery();

    expect($query->toSql())->toContain('user_id')
        ->and($query->getBindings())->toBe([42]);
});

it('allows viewing and editing only the authenticated mailbox record', function () {
    $user = eximUserWithId(42);
    $otherUser = eximUserWithId(43);
    Auth::setUser($user);

    expect(DomainUserResource::canView($user))->toBeTrue()
        ->and(DomainUserResource::canEdit($user))->toBeTrue()
        ->and(DomainUserResource::canView($otherUser))->toBeFalse()
        ->and(DomainUserResource::canEdit($otherUser))->toBeFalse();
});

it('does not expose the mailbox resource through global search', function () {
    Auth::setUser(eximUserWithId(42));

    expect(DomainUserResource::canGloballySearch())->toBeFalse();
});
