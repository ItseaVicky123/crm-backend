<?php

namespace App\Models\Offer;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Models\ReadOnlyModel;
use App\Lib\Scopes\SmcActiveScope;
use App\Traits\ModelImmutable;

/**
 * Model that accesses v_offer_types (readonly).
 * Class Type
 * Reader for the v_offer_types view, uses slave connection.
 * @package App\Models\Offer
 */
class Type extends ReadOnlyModel
{
    use ModelImmutable;

    const TYPE_STANDARD   = 1;
    const TYPE_PREPAID    = 2;
    const TYPE_SEASONAL   = 3;
    const TYPE_SERIES     = 4;
    const TYPE_COLLECTION = 5;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;
    

    /**
     * @var string
     */
    public $table = 'v_offer_types';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'active',
        'smc_active',
    ];

    /**
     * Apply scope so that all() automatically filters out inactive types utilizing the SMC enforcement.
     */
    protected static function booted()
    {
        static::addGlobalScope(new SmcActiveScope);
    }

    /**
     * Get all of the offer type IDs in a unique array.
     * @return array
     */
    public static function allTypeIds(): array
    {
        $data  = [];
        $types = Type::select('id')
            ->groupBy('id')
            ->get();

        if ($types->isNotEmpty()) {
            foreach($types as $type) {
                $data[] = $type->id;
            }
        }

        return $data;
    }

    /**
     * Get the lower case snake version of the name.
     * @return string
     */
    public function lowerSnakeName(): string
    {
        return str_replace(' ', '_', strtolower($this->name));
    }
}
