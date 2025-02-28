<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class ProviderGlobalFieldOption
 * @package App\Models
 */
class ProviderGlobalFieldOption extends Model
{
    use ModelImmutable;

    /**
     * @var string
     */
    protected $table   = 'v_provider_global_field_options';

    /**
     * @var bool
     */
    public $timestamp  = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'option',
    ];
}
