<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class ProviderRequiredFieldOption
 * @package App\Models
 */
class ProviderRequiredFieldOption extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    protected $table   = 'v_provider_required_field_options';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'option',
        'value',
    ];

    /**
     * @var bool
     */
    public $timestamp  = false;
}
