<?php

namespace App\Models\Contact;

use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompositePrimaryKey;
use App\Models\EntityType;

/**
 * Class Relationship
 * @package App\Models
 */
class Relationship extends Model
{
    use Eloquence, HasCompositePrimaryKey;

    const UPDATED_AT = null;

    /**
     * @var string
     */
    public $table = 'contact_relationships';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    public $attributes = [
        'type_id' => 1,
    ];

    /**
     * @var array
     */
    protected $primaryKey = [
        'contact_id',
        'entity_id',
        'entity_type_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'contact_id',
        'entity_type_id',
        'entity_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $guarded = [
        'type_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entity_type()
    {
        return $this->hasOne(EntityType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(RelationshipType::class, 'id', 'type_id');
    }

    /**
     * @param $query
     * @param $typeId
     * @return mixed
     */
    public function scopeForEntityType($query, $typeId)
    {
        return $query->where('entity_type_id', $typeId);
    }

    /**
     * @param $query
     * @param $contactId
     * @return mixed
     */
    public function scopeForContact($query, $contactId)
    {
        return $query->where('contact_id', $contactId);
    }
}
