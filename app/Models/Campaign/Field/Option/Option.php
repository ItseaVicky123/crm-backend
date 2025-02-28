<?php

namespace App\Models\Campaign\Field\Option;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class Option
 * @package App\Models\Campaign
 */
class Option extends Model
{
    use Eloquence, Mappable;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'campaign_field_x_options';

    /**
     * @var array
     */
    protected $maps = [
        'label' => 'option_name',
        'value' => 'option_value',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'schema_field_id',
        'label',
        'value',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'label',
        'value',
    ];

    protected $appends = [
        'label',
        'value',
    ];
}
