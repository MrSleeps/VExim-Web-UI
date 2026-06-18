<?php

namespace App\Repositories\Interfaces;

interface EmailScoreSampleRepositoryInterface
{
    public function create(array $data): void;
}