<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\HasCompositePrimaryKey;

/**
 * Class GatewayFeeType
 * @package App\Models
 *
 * @property Vertical $vertical
 */
class GatewayVertical extends Model
{
    use Eloquence, HasCompositePrimaryKey;

    /**
     * @var array
     */
    protected $primaryKey = [
        'gateway_id',
        'vertical_id',
    ];

    /**
     * @var string
     */
    protected $table      = 'gateway_vertical_jct';

    /**
     * @var array
     */
    protected $fillable   = [
        'gateway_id',
        'vertical_id',
    ];

    /**
     * @var bool
     */
    public $timestamps    = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vertical()
    {
        return $this->hasOne(Vertical::class, 'id', 'vertical_id');
    }
}
