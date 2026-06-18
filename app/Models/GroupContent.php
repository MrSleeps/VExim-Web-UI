<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Models\EximUser;
use App\Models\Group;

class GroupContent extends Pivot
{
    use HasFactory;

    protected $table = 'group_contents';

    protected $fillable = [
        'group_id',
        'member_id'
    ];

    protected $primaryKey = ['group_id', 'member_id'];
    public $incrementing = false;
    
    public function member()
    {
        return $this->belongsTo(EximUser::class, 'member_id');
    }
    
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}