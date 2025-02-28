<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Provider
 * @package App\Models\Campaign
 */
class Provider extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = false;

    /**
     * @var string
     */
    public $table = 'campaign_provider';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'active' => 1,
    ];

    /**
     * @var array
     */
    protected $maps = [
        'created_at' => self::CREATED_AT,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'c_id');
    }
}
