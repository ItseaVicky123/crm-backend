<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Illuminate\Support\Facades\Request;

/**
 * Class Currency
 *
 * @property string $code
 */
class Currency extends Model
{
    use Eloquence, Mappable;

    const UPDATED_AT      = 'last_updated';
    const VALIDATION_RULE = 'required|string|between:1,3';
    const JAPANESE_YEN    = 7;

    /**
     * @var string
     */
    protected $primaryKey = 'currencies_id';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'title',
        'code',
        'validation_rule',
        'symbol_left',
        'symbol_right',
        'decimal_point',
        'thousands_point',
        'decimal_places',
        'html_entity_name',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id' => 'currencies_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
    ];

    /**
     * @param $query
     * @return mixed
     */
    public function scopeDefault($query)
    {
        return $query->where('id', 1);
    }

    /**
     * @param $value
     * @return string
     */
    public function format($value)
    {
        return $this->symbol_left . number_format($value, 2, $this->decimal_point, $this->thousands_point) . $this->symbol_right;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if ($user = Request::user()) {
            if ($user instanceof ApiUser) {
                $this->makeHidden([
                    'symbol_right',
                    'decimal_point',
                    'thousands_point',
                    'decimal_places',
                    'html_entity_name',
                ]);
            }
        }

        return parent::toArray();
    }
}
