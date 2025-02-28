<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SubscriptionFilter
 * @package App\Models
 */
class SubscriptionFilter extends Model
{
    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    public $primaryKey = 'email';

    /**
     * @var array
     */
    protected $fillable = [
        'email',
        'created_at',
    ];
}
