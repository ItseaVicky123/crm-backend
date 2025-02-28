<?php

namespace App\Models\Campaign\Field;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\Field\Option\Option;
use App\Scopes\ActiveScope;

/**
 * Class Field
 * @package App\Models\Campaign
 */
class Field extends Model
{
    use Eloquence, Mappable;

    /**
     * @var string
     */
    protected $table = 'campaign_schema';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $primaryKey = 'schema_id';

    /**
     * @var array
     */
    protected $maps = [
        'id'   => 'schema_id',
        'name' => 'label_name',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'name',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'name',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'label_name',
        'field_name',
        'order_field_name',
        'cc_form_field',
        'is_credit_card_field',
        'is_required',
        'field_order',
        'campaign_id',
        'field_type',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'campaign_step' => 2,
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ActiveScope);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'c_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(Option::class, 'schema_field_id', 'schema_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOptionsAttribute()
    {
        return $this->options()->get();
    }
}
