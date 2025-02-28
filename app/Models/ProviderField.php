<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class ProviderField
 * @package App\Models
 */
class ProviderField extends Model
{
    use Eloquence, ModelImmutable;

    const CREATED_AT = 'created_at';

    /**
     * @var string
     */
    protected $table   = 'v_provider_fields';

    /**
     * @var array
     */
    protected $visible = [
        'api_name',
        'validation_rule',
        'is_multi',
        'options',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'options',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
         return ($this->is_global ?
             $this->hasMany(ProviderGlobalFieldOption::class, 'field_id') :
             $this->hasMany(ProviderRequiredFieldOption::class, 'field_id'));
    }

    public function getOptionsAttribute()
    {
        return $this->options()->get();
    }

    /**
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->attributes['field_name'] ?? '';
    }

    /**
     * @return bool
     */
    public function getIsReadOnlyAttribute()
    {
        return (bool) $this->attributes['is_read_only'];
    }
}
