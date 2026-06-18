<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

class VwPersonalAccessToken extends SanctumToken
{
    // Allows renaming of Personal Access Tokens DB
    protected $table = 'vw_personal_access_tokens';
}