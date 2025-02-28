<?php

namespace App\Models;

use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
/**
 * Class Blacklist
 * @package App\Models
 */
class Blacklist extends Model
{

    use Eloquence, Mappable;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = false;

    /**
     * @var string
     */
    public $table = 'black_list';

    /**
     * @var string
     */
    protected $primaryKey = 'bl_id';

    /**
     * @var array
     */
    protected $guarded = [
        'bl_id',
        'id',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'          => 'bl_id',
        'first_name'  => 'bl_firstname',
        'last_name'   => 'bl_lastname',
        'email'       => 'bl_email',
        'ip_address'  => 'bl_ip',
        'zip'         => 'bl_zipcode',
        'campaign_id' => 'bl_campaign_id',
        'note'        => 'bl_comments',
        'cc_first_6'  => 'charge_c_ins',
        'cc_last_4'   => 'charge_c_mod',
        'cc_length'   => 'charge_c_length',
        'created_at'  => 'createdOn',
        'ip'          => 'bl_ip',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id'          => 'bl_id',
        'first_name'  => 'bl_firstname',
        'last_name'   => 'bl_lastname',
        'email'       => 'bl_email',
        'ip_address'  => 'bl_ip',
        'zip'         => 'bl_zipcode',
        'campaign_id' => 'bl_campaign_id',
        'note'        => 'bl_comments',
        'cc_first_6'  => 'charge_c_ins',
        'cc_last_4'   => 'charge_c_mod',
        'cc_length'   => 'charge_c_length',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'first_name',
        'last_name',
        'email',
        'ip_address',
        'zip',
        'campaign_id',
        'note',
        'cc_first_6',
        'cc_last_4',
        'cc_length',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contact()
    {
        return $this->hasOne(Contact::class, 'email', 'email');
    }
}
