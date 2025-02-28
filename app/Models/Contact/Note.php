<?php

namespace App\Models\Contact;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sofa\Eloquence\Eloquence;
use App\Lib\HasCreator;
use App\Models\User;

/**
 * Class Note
 * @package App\Models
 */
class Note extends Model
{

    use Eloquence, SoftDeletes, HasCreator;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    /**
     * @var bool
     */
    public $timestamps = true;

    /**
     * @var string
     */
    public $table = 'contact_notes';

    /**
     * @var array
     */
    protected $fillable = [
        'value',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'value',
        'creator',
        'updator',
        'created_at',
        'updated_at',
        'deleted_at',
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
        'creator',
        'updator',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function($contactNote) {
            $contactNote->created_by = \current_user(User::SYSTEM);
        });

        static::updating(function($contactNote) {
            $contactNote->updated_by = \current_user(User::SYSTEM);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @param $query
     * @param $contactId
     * @return mixed
     */
    public function scopeForContact($query, $contactId)
    {
        return $query->where('contact_id', $contactId);
    }
}
