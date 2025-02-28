<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagingBatchStatistic extends Model
{
    protected $fillable = [
        'batch_id',
        'name',
        'value',
    ];
    public $timestamps = false;
}
