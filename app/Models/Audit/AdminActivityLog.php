<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;

    protected $fillable = [
        'access_date',
        'admin_id',
        'page_accessed',
        'page_parameters',
        'ip_address'
    ];

}
