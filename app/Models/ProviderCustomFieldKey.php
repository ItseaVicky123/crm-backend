<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\ModelImmutable;

/**
 * Class ProviderCustomFieldKey
 * Reader for the v_provider_custom_field_keys view, uses slave connection.
 * @package App\Models
 */
class ProviderCustomFieldKey extends Model
{
    use ModelImmutable;

    const CREATED_AT = 'created_at';

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_provider_custom_field_keys';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    public $visible = [
        'name',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'name',
    ];

    /**
     * @return mixed
     */
    protected function getNameAttribute()
    {
        return $this->attributes['key'];
    }
}
