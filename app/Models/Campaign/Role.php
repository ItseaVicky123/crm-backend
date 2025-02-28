<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 * @package App\Models\Campaign
 */
class Role extends Model
{
    /**
     * @var string
     */
    protected $table = 'vlkp_campaign_roles';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];
}
