<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\HasCompositePrimaryKey;
use App\Models\Contact\Contact;

/**
 * Class CustomFieldValue
 * @package App\Models
 */
class CustomFieldValue extends Model
{
    use SoftDeletes, Eloquence, Mappable, HasCompositePrimaryKey;

    const MAX_VALUES = 250;

    /**
     * @var array
     */
    protected $primaryKey = [
        'custom_field_id',
        'entity_id',
        'entity_type_id',
        'option_id',
    ];

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'custom_field_id',
        'entity_id',
        'entity_type_id',
        'value',
        'created_by',
        'updated_by',
        'updated_at',
        'option_id',
        'deleted_at',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'value',
        'option_id',
    ];

    protected $appends = [
        'name'
    ];

    protected $maps = [
        'name' => 'custom_field.name',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function($customFieldValue) {
            if (! $customFieldValue->created_by) {
                $customFieldValue->created_by = get_current_user_id();
            }
        });

        static::updating(function($customFieldValue) {
            $customFieldValue->updated_by = get_current_user_id();
        });

        static::deleting(function($customFieldValue) {
            $customFieldValue->updated_by = get_current_user_id();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function custom_field()
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entity_type()
    {
        return $this->hasOne(EntityType::class, 'id', 'entity_type_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'id', 'entity_id');
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        $this->primaryKey = [
            'custom_field_id' => $this->getAttribute('custom_field_id'),
            'entity_id'       => $this->getAttribute('entity_id'),
            'entity_type_id'  => $this->getAttribute('entity_type_id'),
            'option_id'       => $this->getAttribute('option_id'),
        ];

        return parent::delete();
    }
}
