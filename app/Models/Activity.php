<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as BaseActivity;

class Activity extends BaseActivity
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vw_activity_log';

}