<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class AllProvider
 * @package App\Models
 */
class AllProvider extends Model
{
    use Eloquence;
 
    /**
     * @var string
     */
    protected $table   = 'v_all_providers';

    /**
     * @var array
     */
    protected $visible = [
        'provider_type_id',
        'account_id',
        'account_name',
        'account_generic_id',
        'profile_generic_id',
        'profile_id',
        'alias_name',
        'create_date',
        'active_flag',
        'archive_flag',
        'status_formatted',
    ];

    /**
     * @var bool
     */
    public $timestamp  = false;
}
