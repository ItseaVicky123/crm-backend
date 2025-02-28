<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\HasCompositePrimaryKey;

class ValueAddServiceConfiguration extends Model
{
    use Mappable, Eloquence, HasCompositePrimaryKey;

    const CREATED_AT = 'date_in';
    const UPDATED_AT = 'update_in';

    /**
     * @var array
     */
    protected $primaryKey = [
        'service_id',
        'key',
    ];

    /**
     * @var string
     */
    protected $table = 'value_add_service_configuration';

    /**
     * @var array
     */
    protected $maps = [
        'created_at' => 'date_in',
        'updated_at' => 'update_in',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'priority',
        'is_readonly',
        'looker_group_id',
        'type_data'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'type_data' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(ValueAddService::class, 'service_id', 'service_id');
    }
}
