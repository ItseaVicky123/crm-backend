<?php


namespace App\Models\BillingModel;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Mappable;
use Sofa\Eloquence\Eloquence;

class Date extends Model
{
    use Mappable, Eloquence;

    /**
     * @var string
     */
    protected $table = 'billing_frequency_dates';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $maps = [
        'billing_model_id' => 'frequency_id',
        'month'            => 'billing_month',
        'day'              => 'billing_day',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'billing_model_id',
        'month',
        'day',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'billing_model_id',
        'month',
        'day',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'billing_model_id',
        'month',
        'day',
    ];
}
