<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductBundleType
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 */
class ProductBundleType extends Model
{
    use ModelImmutable;

    const PREBUILT = 1;
    const CUSTOM   = 2;

    /**
     * @var string
     */
    protected $table  = 'vlkp_bundle_type';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
    ];

    /**
     * @return int
     */
    public static function getPrebuiltType(): int
    {
        return self::PREBUILT;
    }

    /**
     * @return int
     */
    public static function getCustomType(): int
    {
        return self::CUSTOM;
    }
}
