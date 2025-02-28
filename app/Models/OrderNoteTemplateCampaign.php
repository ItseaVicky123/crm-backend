<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * Class OrderNoteTemplateCampaign
 * @package App\Models
 */
class OrderNoteTemplateCampaign extends Model
{
    use Eloquence, Mappable;

    const CREATED_AT = 'date_in';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'order_note_campaign_jct';

    /**
     * @var array
     */
    protected $fillable = [
        'note_id',
        'campaign_id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'note_id'     => 'note_profile_id',
        'campaign_id' => 'c_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'campaign_id',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'campaign_id',
    ];
}
