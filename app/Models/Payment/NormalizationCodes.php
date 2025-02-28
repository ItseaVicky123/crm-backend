<?php

namespace App\Models\Payment;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class NormalizationCodes
 * Reader for the v_normalization_codes view, uses slave connection.
 * @package App\Models\Payment
 *
 * @property string $normal_code   Normalized Code from Database
 * @property string $response_text Normalized Response Text from Database
 */
class NormalizationCodes extends Model
{
    use ModelImmutable;

    public const SMC = 'USE_NORMALIZATION_CODES';

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;

    /**
     * @var string
     */
    protected $table = 'v_normalization_codes';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $maps = [
        'normal_code'   => 'normalCode',
        'response_text' => 'respText'
    ];
}
