<?php

namespace App\Models;

use Spatie\Health\Models\HealthCheckResultHistoryItem as BaseModel;

class HealthCheckResultHistoryItem extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vw_health_check_result_history_items';
}
