<?php

namespace App\Models\Contact;

use App\Models\Blacklist;
use App\Models\Address;
use App\Models\Payment\ContactPaymentSource;
use App\Models\Customer;
use App\Models\EntityType;
use App\Models\Order;
use App\Models\Prospect;

/**
 * Class Contact
 * @package App\Models
 */
class Contact extends Base
{
    const ENTITY_ID = 13;
    const COMMUNICATION_OPT_EMAIL = 1;
    const COMMUNICATION_OPT_SMS = 2;
    const COMMUNICATION_TYPES = [
        self::COMMUNICATION_OPT_EMAIL,
        self::COMMUNICATION_OPT_SMS,
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var int
     */
    public $entity_type_id = self::ENTITY_ID;

    /**
     * @var array
     */
    protected $fillable = [
        'email',
        'origin_id',
        'is_member',
        'phone',
        'first_name',
        'last_name',
        'opted_communications',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'custom_fields',
        'addresses',
        'notes',
        'is_sms_communication_enabled',
    ];

    /**
     * @var array
     */
    protected $hidden = [
        'opted_communications',
    ];

    public static function boot()
    {
        parent::boot();


        static::creating(function($contact) {
            $contact->setPhoneKeyAttribute();

            if (! $contact->email){
                \fileLogger::log_warning("No email detected when creating Contact for: {$contact->first_name} {$contact->last_name}");
            }
        });

        static::updating(function ($contact) {
            $contact->setPhoneKeyAttribute();
        });
    }

    /**
     * @return $this
     */
    public function setPhoneKeyAttribute()
    {
        $this->attributes['phone_key'] = preg_replace('~\D+~', '', $this->getAttribute('phone'));

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'customers_email_address', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function customer()
    {
        return $this->hasOne(Customer::class, 'customers_email_address', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function prospects()
    {
        return $this->hasMany(Prospect::class, 'pEmail', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function prospect()
    {
        return $this->belongsTo(Prospect::class, 'pEmail', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function blacklists()
    {
        return $this->hasMany(Blacklist::class, 'bl_email', 'email');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function blacklist()
    {
        return $this->hasOne(Blacklist::class, 'bl_email', 'email');
    }

    /**
     * @return mixed
     */
    protected function getIsBlacklistedAttribute()
    {
        return $this->blacklist()->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function orders()
    {
        $table = Relationship::make()->getTable();

        return $this->hasManyThrough(
            Order::class,
            Relationship::class,
            'contact_id',
            'orders_id',
            'id',
            'entity_id'
        )->where("{$table}.entity_type_id", Order::ENTITY_ID);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function relationships()
    {
        return $this->hasMany(Relationship::class, 'contact_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function entity_type_id()
    {
        return $this->hasOne(EntityType::class)
            ->where('id', '=', self::ENTITY_ID);
    }

    /**
     * @return mixed
     */
    public function getEmailAttribute()
    {
        return $this->attributes['email'];
    }

    /**
     * @return int
     */
    public function getContactIdAttribute()
    {
        return $this->getAttribute('id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class, 'contact_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAddressesAttribute()
    {
        return $this->addresses()->get();
    }

    /**
     * @return Object|null
     */
    public function getDefaultAddressAttribute()
    {
        return $this->addresses()->where('is_default', 1)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payment_sources()
    {
        return $this->hasMany(ContactPaymentSource::class, 'contact_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(Note::class, 'contact_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getNotesAttribute()
    {
        return $this->notes()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function interests()
    {
        return $this->hasMany(Interest::class, 'contact_id', 'id');
    }

    /**
     * @return int
     */
    public function getIsSmsCommunicationEnabledAttribute()
    {
        return (int) (($this->opted_communications & self::COMMUNICATION_OPT_SMS) > 0);
    }

    /**
     * @return int
     */
    public function getIsEmailCommunicationEnabledAttribute()
    {
        return (int) (($this->opted_communications & self::COMMUNICATION_OPT_EMAIL) > 0);
    }

    /**
     * @return mixed|string
     */
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
