<?php

namespace App\Repositories;

use App\Models\EmailScoreSample;
use App\Repositories\Interfaces\EmailScoreSampleRepositoryInterface;

class EmailScoreSampleRepository implements EmailScoreSampleRepositoryInterface
{
    protected $model;

    public function __construct(EmailScoreSample $model)
    {
        $this->model = $model;
    }

    public function create(array $data): void
    {
        $this->model->create($data);
    }
}