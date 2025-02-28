<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Scopes\ActiveScope;

class OrderHistoryNoteType extends Model
{
    use Eloquence, Mappable;

    public const TYPE_NOTES = 'notes';

    protected $table = 'tlkp_orders_history_type';

    /**
     * @var array
     */
    protected $visible = [
        'name'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope());
    }

    public function order_history_note()
    {
        return $this->belongsTo(OrderHistoryNote::class, 'type', 'type_id');
    }
}
