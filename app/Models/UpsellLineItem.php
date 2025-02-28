<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Traits\LineItem;
use App\Scopes\OrderLineItemScope;

class UpsellLineItem extends Model
{
    use LineItem, Eloquence, Mappable;

    protected $table = 'upsell_orders_total';
    protected $primaryKey = 'upsell_orders_total_id';

    protected $visible = [
        'id',
        'upsell_id',
        'title',
        'text',
        'value',
        'class',
        'sort_order',
    ];

    protected $appends = [
        'id',
        'upsell_id',
    ];


    protected $maps = [
        'id'        => 'upsell_orders_total_id',
        'upsell_id' => 'upsell_orders_id',
    ];

    protected $fillable = [
        'upsell_id',
        'sort_order',
        'value',
        'text',
    ];

    protected $currencyId = 1;

    public $timestamps = false;

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new OrderLineItemScope);

        static::creating(function ($item) {
            if (! $item->getAttribute('class')) {
                if (! defined('static::CLASS_NAME')) {
                    throw new \Exception('Cannot create without class name');
                }

                $item->setAttribute('class', static::CLASS_NAME);
            }

            $item->generate();
        });

        static::updating(function ($item) {
            $item->setAttribute('text', $item->formatText());
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Upsell::class, 'upsell_orders_id', 'upsell_orders_id');
    }
}
