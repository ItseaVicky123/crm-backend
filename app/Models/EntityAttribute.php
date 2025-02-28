<?php

namespace App\Models;

use App\Exceptions\EntityAttributeImmutableException;
use App\Scopes\EntityAttributeNameScope;
use App\Scopes\EntityTypeIdScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class EntityAttribute
 * @package App\Models
 */
class EntityAttribute extends Model
{
    use Eloquence, Mappable;

    const IS_IMMUTABLE = false;
    const IGNORE_DUPLICATES = false;
    const CREATED_AT = null;
    const UPDATED_AT = null;

    /**
     * @var string
     */
    protected $table = 'entity_attribute';

    /**
     * @var array
     */
    protected $fillable = [
        'entity_type_id',
        'entity_primary_id',
        'attr_name',
        'attr_value',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'type_id',
        'primary_id',
        'attribute_name',
        'attribute_value',
    ];
    /**
     * @var string[]
     */
    protected $maps = [
        'type_id'         => 'entity_type_id',
        'primary_id'      => 'entity_primary_id',
        'attribute_name'  => 'attr_name',
        'attribute_value' => 'attr_value',
    ];
    /**
     * @var string[]
     */
    protected $appends = [
        'type_id',
        'primary_id',
        'attribute_name',
        'attribute_value',
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new EntityTypeIdScope);
        static::addGlobalScope(new EntityAttributeNameScope);
    }

    /**
     * @param       $entityId
     * @param null  $value
     * @param false $ignoreDuplicate
     * @return null
     * @throws \App\Exceptions\EntityAttributeImmutableException
     */
    public static function createForEntity($entityId, $value = null, $ignoreDuplicate = false)
    {
        $value = $value ?? static::DEFAULT_VALUE;

        if (is_null($value)) {
            Log::debug('No value set for ' . __METHOD__);

            return null;
        }

        $existing = static::where('entity_primary_id', $entityId);

        if ($attr = $existing->first()) {
            if ($ignoreDuplicate || static::IGNORE_DUPLICATES) {
                return $attr;
            }

            if (static::IS_IMMUTABLE) {
                throw new EntityAttributeImmutableException(get_called_class() . ' is immutable');
            }

            $attr->attr_value = $value;

            // Removing the save functionality here because it cannot be done properly without a primary key and this table doesnt have one
            // using update to be proper and accurate on the row that needs to be updated
            $attr->where('entity_type_id', static::TYPE_ID)
                ->where('entity_primary_id', $entityId)
                ->where('attr_name', static::ATTRIBUTE_NAME)
                ->update(['attr_value' => $value]);

            return $attr;
        } else {
            return self::create([
                'entity_type_id'    => static::TYPE_ID,
                'entity_primary_id' => $entityId,
                'attr_value'        => $value,
            ]);
        }
    }
}
