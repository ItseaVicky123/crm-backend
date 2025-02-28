<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\ModelImmutable;

/**
 * Class UserSetupGuide
 * Reader for the v_setup_guide view, uses slave connection.
 * @package App\Models
 */
class UserSetupGuide extends Model
{
    use Eloquence, Mappable, ModelImmutable;

    const FEATURE_ID = 1;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'v_setup_guide';

    /**
     * @var array
     */
    protected $visible = [
        'link',
        'role_id',
        'is_optional',
        'is_complete',
        'category',
        'name',
        'description',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'is_complete' => 'status_flag',
        'category'    => 'category_name',
        'name'        => 'step_name',
        'description' => 'step_description',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_complete',
        'category',
        'name',
        'description',
    ];

    function getStepDescriptionAttribute()
    {
       return str_replace('{company}', \current_domain::company_name(), $this->attributes['step_description']);
    }

}
