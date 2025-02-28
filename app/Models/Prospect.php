<?php

namespace App\Models;

use App\Traits\CampaignPermissions;
use Carbon\Carbon;
use App\Lib\Lime\LimeSoftDeletes;
use App\Models\Campaign\Campaign;
use App\Models\Contact\Contact;
use App\Models\Contact\Base as ContactBase;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Prospect
 * @package App\Models
 */
class Prospect extends ContactBase
{
    use LimeSoftDeletes, CampaignPermissions;

    const ACTIVE_FLAG = false;
    const CREATED_AT  = 'pDate';
    const UPDATED_AT  = 'update_in';
    const ENTITY_ID   = 5;

    /**
     * @var int
     */
    public $entity_type_id = self::ENTITY_ID;

    /**
     * @var string
     */
    protected $primaryKey = 'prospects_id';

    /**
     * @var array
     */
    protected $dates = ['pDate', 'update_in'];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'campaign_id',
        'contact_id',
        // Flags
        'sent_prospect',
        // Dates
        'created_at',
        'updated_at',
        // Customer
        'email',
        'phone',
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'state_id',
        'country',
        'zip',
        // Misc
        'ip_address',
        'ip_location',
        'notes',
        'afid',
        'sid',
        'affid',
        'c1',
        'c2',
        'c3',
        'bid',
        'aid',
        'click_id',
        'custom_fields',
        'custom_field_values',
        'order_customer_types',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'email',
        'phone',
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'state_id',
        'zip',
        'campaign_id',
        'country',
        'ip_address',
        'ip_location',
        'note',
        'afid',
        'sid',
        'affid',
        'c1',
        'c2',
        'c3',
        'bid',
        'aid',
        'click_id',
        'opt',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'campaign_id',
        'state',
        'state_id',
        // Flags
        'sent_prospect',
        'is_member',
        // Contact
        'contact',
        'contact_id',
        // Customer
        'email',
        'phone',
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        // Misc
        'click_id',
        'ip_address',
        'ip_location',
        'notes',
        'afid',
        'sid',
        'affid',
        'c1',
        'c2',
        'c3',
        'bid',
        'aid',
        // Dates
        'created_at',
        'updated_at',
        'custom_fields',
        'custom_field_values',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'            => 'prospects_id',
        // Flags
        'sent_prospect' => 'pSentProspect',
        'is_active'     => 'active',
        'is_deleted'    => 'deleted',
        // Dates
        'created_at'    => 'pDate',
        'updated_at'    => 'update_in',
        // Customer
        'email'         => 'pEmail',
        'phone'         => 'pPhone',
        'first_name'    => 'pFirstName',
        'last_name'     => 'pLastName',
        'address'       => 'pAddress',
        'address2'      => 'pAddress2',
        'city'          => 'pCity',
        'zip'           => 'pZip',
        // Misc
        'click_id'      => 'pClickID',
        'afid'          => 'pAFID',
        'sid'           => 'pSID',
        'affid'         => 'pAFFID',
        'c1'            => 'pC1',
        'c2'            => 'pC2',
        'c3'            => 'pC3',
        'bid'           => 'pBID',
        'aid'           => 'pAID',
        'opt'           => 'pOPT',
        'updated_by'    => 'update_id',
        'created_by'    => 'create_id',
    ];

    /**
     * @var array
     */
    protected $attributes = [
        'pFirstName'         => '',
        'pLastName'          => '',
        'pAddress'           => '',
        'pAddress2'          => '',
        'pCity'              => '',
        'pState'             => '',
        'pZip'               => '',
        'pCountry'           => '',
        'pPhone'             => '',
        'pEmail'             => '',
        'pIPAddressLocation' => '',
        'pClickID'           => '',
    ];

    // IDs

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $state_id;

    /**
     * @var int
     */
    protected $campaign_id;


    // Contact
    protected $contact;

    /**
     * @var int
     */
    protected $contact_id;

    /**
     * @var array
     */
    protected $notes = [];

    /**
     * @var bool
     */
    protected $state_set = false;

    /**
     * @var bool
     */
    protected $state_id_set = false;

    public static function boot()
    {
        parent::boot();

        static::applyCampaignPermissionsBoot();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contact()
    {
        return $this->hasOne(Contact::class, 'email', 'pEmail');
    }

    /**
     * Eloquent Mutator converts notes
     *
     *
     * @return array
     */
    public function getNotesAttribute()
    {
        if (isset($this->attributes['pNotes']) && ! count($this->notes) && is_array($notes = unserialize(base64_decode($this->attributes['pNotes'])))) {
            foreach ($notes as $note_parts) {
                while ($temp_note = base64_decode($note_parts['note'], true)) {
                    if (mb_check_encoding($temp_note, 'UTF-8')) {
                        $note_parts['note'] = $temp_note;

                        if (preg_match('~^(\d+)$~', $note_parts['user'])) {
                            $user_name = User::find($note_parts['user'])->name;
                        } else {
                            $user_name = $note_parts['user'];
                        }

                        $this->notes[]      = [
                            'created_at' => Carbon::createFromTimeString($note_parts['timestamp']),
                            'user'       => $user_name,
                            'note'       => $temp_note,
                        ];
                    } else {
                        $note_parts['note'] = false;
                    }
                }
            }
        }

        return $this->notes;
    }

    public function setNoteAttribute($value)
    {
        $new_note_final = [];
        $new_note_array = [
            'user'      => \current_user(\history_note_base::LIME_LIGHT_ADMIN),
            'timestamp' => Carbon::now()->format('Y-m-d H:i'),
            'note'      => base64_encode($value),
        ];

        $past_notes = $this->attributes['pNotes'];

        if (! empty($past_notes)) {
            $past_notes_string = base64_decode($past_notes);
            $past_notes_array  = unserialize($past_notes_string);

            array_push($past_notes_array, $new_note_array);

            $val_notes = serialize($past_notes_array);
            $val_notes = base64_encode($val_notes);
        } else {
            $new_note_final[] = $new_note_array;
            $val_notes        = serialize($new_note_final);
            $val_notes        = base64_encode($val_notes);
        }

        $this->attributes['pNotes'] = $val_notes;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function country()
    {
        return $this->hasOne(Country::class, 'countries_id', 'pCountry');
    }

    /**
     * @return mixed
     */
    public function getCountryAttribute()
    {
        return $this->country()->first()->iso_2;
    }

    /**
     * @return mixed
     */
    public function getEmailAttribute()
    {
        return $this->attributes['pEmail'];
    }

    /**
     * @return mixed
     */
    public function getCampaignIdAttribute()
    {
        return $this->attributes['campaign_id'];
    }

    /**
     * @param $value
     */
    public function setStateAttribute($value)
    {
        $this->attributes['pState'] = \GetStateName($value, $this->getAttribute('country_id')) ?? '';
        $this->state_set            = true;

        if (! $this->state_id_set) {
            $this->setStateIdAttribute($value);
        }
    }

    /**
     * @param $value
     */
    public function setStateIdAttribute($value)
    {
        $this->attributes['pState_id'] = \GetStateId($value, $this->getAttribute('country_id')) ?? '';
        $this->state_id_set            = true;

        if (! $this->state_set) {
            $this->setStateAttribute($value);
        }
    }

    /**
     * @param $value
     */
    public function setIpAddressAttribute($value)
    {
        $this->attributes['pIPAddress'] = $value;
    }

    /**
     * @return mixed
     */
    public function getIpAddressAttribute()
    {
        return $this->attributes['pIPAddress'];
    }

    public function setIpLocationAttribute($value)
    {
        if (! $value && $this->attributes['pIPAddress']) {
            $value = (new \geo_location())->get($this->attributes['pIPAddress']);
        }

        $this->attributes['pIPAddressLocation'] = $value;
    }

    /**
     * @return mixed
     */
    public function getIpLocationAttribute()
    {
        if (strpos($this->attributes['pIPAddressLocation'], ',  ') === 0) {
            $this->attributes['pIPAddressLocation'] = str_replace(',  ', '', $this->attributes['pIPAddressLocation']);
        }

        return $this->attributes['pIPAddressLocation'];
    }

    /**
     * @return string | null
     */
    public function getStateAttribute()
    {
        return $this->attributes['pState'];
    }

    /**
     * @return string | null
     */
    public function getStateIdAttribute()
    {
        return $this->attributes['pState_id'];
    }

    /**
     * @return int
     */
    public function getIsMemberAttribute()
    {
        if (! $contact = $this->contact()->first()) {
            return 0;
        }

        return (int) $contact->is_member;
    }

    /**
     * @param $value
     */
    public function setPAddress2Attribute($value)
    {
        $this->attributes['pAddress2'] = $value ?? '';
    }

    /**
     * @param $value
     */
    public function setPClickIDAttribute($value)
    {
        $this->attributes['pClickID'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPAFIDAttribute($value)
    {
        $this->attributes['pAFID'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPSIDAttribute($value)
    {
        $this->attributes['pSID'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPAFFIDAttribute($value)
    {
        $this->attributes['pAFFID'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPC1Attribute($value)
    {
        $this->attributes['pC1'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPC2Attribute($value)
    {
        $this->attributes['pC2'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPC3Attribute($value)
    {
        $this->attributes['pC3'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPBIDAttribute($value)
    {
        $this->attributes['pBID'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPAIDAttribute($value)
    {
        $this->attributes['pAID'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPOPTAttribute($value)
    {
        $this->attributes['pOPT'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPPhoneAttribute($value)
    {
        $this->attributes['pPhone'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPZipAttribute($value)
    {
        $this->attributes['pZip'] = $value ?: '';
    }

    /**
     * @param $value
     */
    public function setPCountryAttribute($value)
    {
        $this->setCountry($value);
    }

    /**
     * @param $value
     */
    public function setCountryAttribute($value)
    {
        $this->setCountry($value);
    }

    /**
     * @param $value
     */
    protected function setCountry($value)
    {
        $this->attributes['pCountry'] = (
        is_numeric($value) ?
            $value :
            \GetCountryId($value, null) ?? ''
        );
    }

    public function setPFirstNameAttribute($value)
    {
        $this->attributes['pFirstName'] = mb_detect_encoding($value, 'UTF-8', true) ? utf8_to_latin1($value) : $value;
    }

    public function setPLastNameAttribute($value)
    {
        $this->attributes['pLastName'] = mb_detect_encoding($value, 'UTF-8', true) ? utf8_to_latin1($value) : $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'c_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entity_attributes()
    {
        return $this->hasMany(EntityAttribute::class, 'entity_primary_id', 'prospects_id')
            ->where('entity_type_id', self::ENTITY_ID);
    }

    /**
     * @return bool
     */
    public function isAtRisk()
    {
        $entityAttribute = $this->entity_attributes()
            ->where('attr_name', 'risk_flag')
            ->where('attr_value', '>', 0);

        return $entityAttribute->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function gift()
    {
        return $this->hasMany(GiftOrder::class, 'email', 'pEmail');
    }

    /**
     * @return int
     */
    public function getContactIdAttribute()
    {
        if (!$email = $this->getEmailAttribute()) {
            $email = Prospect::find($this->attributes['prospects_id'])->email;
        }

        if (!$this->contact) {
            $this->contact = Contact::firstOrCreate([
                'email'      => $email,
            ], [
                'first_name' => $this->getAttribute('first_name'),
                'last_name'  => $this->getAttribute('last_name'),
                'phone'      => $this->getAttribute('phone'),
            ]);
        }

        return $this->contact->getAttribute('id');
    }

    /**
     * @return HasMany
     */
    public function order_customer_types(): HasMany
    {
        return $this->hasMany(OrderCustomerType::class, 'prospect_id', 'prospects_id');
    }
}
