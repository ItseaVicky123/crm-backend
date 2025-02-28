<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\HasCompositePrimaryKey;

/**
 * Class GatewayFee
 * @package App\Models
 */
class GatewayFee extends Model
{
    use Eloquence, HasCompositePrimaryKey;

    /**
     * @var string
     */
    protected $table      = 'gateway_fee';

    /**
     * @var array
     */
    protected $primaryKey = [
        'gateway_id',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $fillable   = [
        'type_id',
        'value',
    ];

    public $timestamps    = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function fee()
    {
        return $this->hasOne(GatewayFeeType::class, 'id', 'type_id');
    }

    public function getFeeAttribute()
    {
        return $this->fee()->first();
    }
}
