<?php

namespace App\Models\Contact;

use Sofa\Eloquence\Eloquence;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AutoresponderList
 * @package App\Models
 */
class AutoresponderList extends Model
{
    use Eloquence;

    /**
     * @var string
     */
    public $table = 'contact_autoresponder_lists';

    /**
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'provider_profile_id',
        'list_id',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'id', 'contact_id');
    }
}
