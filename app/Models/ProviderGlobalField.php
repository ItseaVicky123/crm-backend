<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class ProviderGlobalField
 * @package App\Models
 */
class ProviderGlobalField extends Model
{
    use ModelImmutable;

    /**
     * @var string
     */
    protected $table   = 'v_provider_global_fields';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options()
    {
        return $this->hasMany(ProviderGlobalFieldOption::class, 'field_id');
    }
}
