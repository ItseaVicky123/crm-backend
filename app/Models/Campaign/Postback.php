<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Models\Postback as PostbackProfile;

/**
 * Class Postback
 * @package App\Models\Campaign
 */
class Postback extends Model
{
    use Eloquence;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var string
     */
    protected $table = 'campaign_postbacks';

    /**
     * @var array
     */
    protected $fillable = [
        'postback_id',
        'campaign_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function postback()
    {
        return $this->hasOne(PostbackProfile::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function campaign()
    {
        return $this->hasOne(Campaign::class, 'c_id', 'campaign_id');
    }
}
