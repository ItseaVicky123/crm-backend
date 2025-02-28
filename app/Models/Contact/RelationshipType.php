<?php

namespace App\Models\Contact;

use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class RelationshipType
 * Reader for the v_contact_relationship_types view, uses slave connection.
 * @package App\Models\Contact
 */
class RelationshipType extends Model
{
    use Eloquence, ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    public $table = 'v_contact_relationship_types';

    /**
     * @var array
     */
    protected $guarded = [
        'id',
        'name',
    ];
}
