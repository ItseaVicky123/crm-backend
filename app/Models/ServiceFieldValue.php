<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompositePrimaryKey;

/**
 * Class ServiceFieldValue
 * @package App\Models
 */
class ServiceFieldValue extends Model
{
    use HasCompositePrimaryKey;

    /**
     * @var string[]
     */
    public $primaryKey = [
        'service_id',
        'field_id',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'service_id',
        'field_id',
        'value',
    ];

    /**
     * @var string[]
     */
    protected $with = [
        'field',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function field()
    {
        return $this->hasOne(ProviderField::class, 'id', 'field_id');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'value' => $this->value,
            'name'  => $this->field->api_name,
        ];
    }
}
