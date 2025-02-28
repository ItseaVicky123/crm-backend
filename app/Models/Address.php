<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Sofa\Eloquence\Eloquence;
use App\Lib\HasCreator;
use App\Models\Contact\Contact;
use App\Traits\HasUuidFind;

/**
 * Class Address
 * @package App\Models
 */
class Address extends Model
{
    use SoftDeletes, Eloquence, HasCreator, HasUuidFind;

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
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'street_2',
        'city',
        'state',
        'zip',
        'country_id',
        'contact_id',
        'is_default',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_key',
        'street',
        'street_2',
        'city',
        'state',
        'zip',
        'is_default',
        // Appended
        'country',
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
        'country',
        'creator',
        'updator',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            $address->setPhoneKeyAttribute();

            if (! $address->created_by) {
                $address->created_by = get_current_user_id();
            }

            if (count($address->contact->addresses) === 0) {
               $address->is_default = 1;
            }

            if (! $address->uuid) {
                $address->uuid = Str::uuid();
            }
        });

        static::updating(function ($address) {
            $address->setPhoneKeyAttribute();
            $address->updated_by = get_current_user_id();
        });

        static::deleting(function ($address) {
            $address->is_default = 0;
        });
    }

    public function setPhoneKeyAttribute()
    {
        $this->attributes['phone_key'] = preg_replace('~\D+~', '', $this->getPhoneAttribute());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function country()
    {
        return $this->hasOne(Country::class, 'countries_id', 'country_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getCountryAttribute()
    {
        return $this->country()->first()->iso_2;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    /**
     * @return string
     *
     */
    public function getEmailAttribute()
    {
        return $this->attributes['email'] ?? $this->contact->email;
    }

    /**
     * @return string
     *
     */
    public function getFirstNameAttribute()
    {
        return $this->attributes['first_name'] ?? $this->contact->first_name;
    }

    /**
     * @return string
     *
     */
    public function getLastNameAttribute()
    {
        return $this->attributes['last_name'] ?? $this->contact->last_name;
    }

    /**
     *
     *
     * @return string
     */
    public function getPhoneAttribute()
    {
        return $this->attributes['phone'] ?? $this->contact->phone;
    }

    public function getVisible()
    {
        $visible = $this->visible;

        if (Auth::user() instanceof User) {
            $visible[] = 'country_id';
        }

        return $visible;
    }

    /**
     *
     * Find an address model, or create it if it doesn't exist
     *
     * @param \App\Models\Contact\Contact $contact
     * @param \App\Models\Country $country
     * @param $street
     * @param $city
     * @param $state
     * @param $zip
     * @param null $street2
     * @return static
     */
    public static function findOrCreate(Contact $contact, Country $country, $street, $city, $state, $zip, $street2 = null): self
    {
        return self::firstOrCreate([
            'contact_id' => $contact['id'],
            'country_id' => $country['id'],
            'street'     => $street,
            'city'       => $city,
            'state'      => $state,
            'zip'        => $zip
        ], [ // Arg 2 gets merged into arg 1 if we have to create a record
            'street_2'   => $street2,
            'first_name' => $contact['first_name'],
            'last_name'  => $contact['last_name'],
            'phone'      => $contact['phone'],
            'email'      => $contact['email'],
        ]);
    }
}
