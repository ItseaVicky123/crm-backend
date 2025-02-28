<?php


namespace App\Models;

use App\Models\Credits\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sofa\Eloquence\Eloquence;
use App\Lib\HasCreator;
use App\Scopes\ItemTypeIdScope;

/**
 * Class Credit
 * @package App\Models\Credit
 */
class Credit extends Model
{
    use SoftDeletes, Eloquence, HasCreator;

    const CREATED_BY = 'created_by';

    const UPDATED_BY = 'updated_by';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var array
     */
    protected $fillable = [
        'item_type_id',
        'item_id',
        'amount',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'item_id',
        'amount',
        // Appended
        'type',
        'creator',
        'updator',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'type',
        'creator',
        'updator',
    ];

    /**
     * @var string
     */
    public $table = 'credits';

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ItemTypeIdScope);

        static::creating(function ($credit) {
            $credit->created_by = get_current_user_id();
        });

        static::updating(function ($credit) {
            $credit->updated_by = get_current_user_id();
        });

        static::deleting(function ($credit) {
            $credit->amount     = 0;
            $credit->updated_by = get_current_user_id();
        });

        static::restoring(function ($credit) {
            $credit->created_by = get_current_user_id();
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(Type::class, 'id', 'item_type_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getTypeAttribute()
    {
        return $this->type()->first();
    }

    /**
     * @return float|string
     */
    public function __toString()
    {
        return (float) $this->amount;
    }
}
