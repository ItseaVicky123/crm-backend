<?php

namespace App\Models;

use App\Traits\CampaignPermissions;
use Carbon\Carbon;
use App\Models\Contact\Base as ContactBase;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Customer
 * @package App\Models
 */
class Customer extends ContactBase
{
    use CampaignPermissions;

    const ACTIVE_FLAG = false;
    const CREATED_AT  = 'date_in';
    const UPDATED_AT = null;
    const ENTITY_ID   = 4;

    /**
     * @var int
     */
    public $entity_type_id = self::ENTITY_ID;

    /**
     * @var string
     */
    protected $primaryKey = 'customers_id';

    /**
     * @var array
     */
    protected $dates = [
        'date_in',
        'customers_dob',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'dob',
        'gender',
        'first_name',
        'last_name',
        'nick_name',
        'email',
        'phone',
        'address_id',
        'tax_exemption',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'gender',
        'email',
        'phone',
        'fax',
        'first_name',
        'last_name',
        'nick_name',
        // Flags
        'is_member',
        // Dates
        'created_at',
        'dob',
        // Appended
        'custom_fields',
        'tax_exemption',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'gender',
        'email',
        'phone',
        'fax',
        'first_name',
        'last_name',
        'nick_name',
        // Dates
        'created_at',
        'dob',
        // Contact
        'contact_id',
        // Entity
        'entity_type_id',
        // Custom Fields
        'custom_fields',
        //additional emails
        'customer_emails',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'         => 'customers_id',
        // Dates
        'created_at' => 'date_in',
        'dob'        => 'customers_dob',
        // Customer
        'email'      => 'customers_email_address',
        'phone'      => 'customers_telephone',
        'fax'        => 'customers_fax',
        'first_name' => 'customers_fname',
        'last_name'  => 'customers_lname',
        'nick_name'  => 'customers_nick',
        'gender'     => 'customers_gender',
        'address_id' => 'customers_default_address_id'
    ];

    /**
     * @var null
     */
    protected $contact = null;

    public static function boot()
    {
        parent::boot();

        // BE AWARE as this global scope will prevent you from finding Customer that don't have any orders yet
        // OR have orders but your current user permissions don't allow you access related campaign.
        // This could cause a customer duplication if you don't use withoutGlobalScopes() to make sure customer email exists
        static::applyCampaignPermissionsBoot('orders');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customers_id', 'customers_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customer_emails()
    {
        return $this->hasMany(CustomerEmail::class, 'customers_id', 'customers_id');
    }

    /**
     * @return array
     */
    public function getCustomerEmailsAttribute()
    {
        return $this->customer_emails()->pluck('email')->all();
    }

    /**
     * First need to find Customer with possible two different emails, if not found check customer email
     * and create one if not found at all
     *
     * @param string $email
     * @param array $data
     * @return \App\Models\Customer
     */
    public static function createOrUpdateCustomerByEmail(string $email, $data = []): Customer
    {
        $customer = Customer::where(['customers_email_address' => $email])->first();
        if (! $customer) {
            //if customer not found we make sure new email is not exist
            if ($email != $data['email']) {
                $customer = Customer::where(['customers_email_address' => $data['email']])->first();
            }

            //if customer not found last check in customer emails
            if (! $customer) {
                $updatingEmail = $data['email'];
                $customer = Customer::whereHas('customer_emails', function($customerEmails) use($email, $updatingEmail) {
                                $customerEmails->where('email', $email)->orWhere('email', $updatingEmail);
                            })->first();
            }
        } else if ($email != $data['email'] && ! CustomerEmail::where(['email' => $data['email']])->exists()) {
            //customer found and this is not main email create new customer email record
            $customer->customer_emails()->create(['email' => $data['email']]);
        }

        //all above scenarios failed to find "customer record" then create new one
        if (! $customer) {
            $customer = Customer::create([
                'email'      => $email,
                'phone'      => $data['phone'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'address_id' => $data['default_address_id'],
                'created_at' => Carbon::now(),
            ]);

            if ($email != $data['email']) {
                $customer->customer_emails()->create(['email' => $email]);
            }
        } else {
            $customer->update(['address_id' => $data['default_address_id']]);
        }

        return $customer;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function contact()
    {
        return $this->hasOne(Contact::class, 'email', 'customers_email_address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function tax_exemption()
    {
        return $this->hasOne(TaxExemption::class, 'id', 'tax_exemption');
    }

    /**
     * @return mixed
     */
    public function getEmailAttribute()
    {
        return $this->attributes['customers_email_address'];
    }


    /**
     * @param $value
     */
    public function setCustomersGenderAttribute($value)
    {
        $this->attributes['customers_gender'] = strtoupper($value);
    }

    /**
     * @param $value
     */
    public function setCustomersDobAttribute($value)
    {
        $this->attributes['customers_dob'] = Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * @return mixed
     */
    public function getOrdersAttribute()
    {
        return $this->orders()->get();
    }

    /**
     * @return array
     */
    public function getOrderIdsAttribute()
    {
        return $this->orders()->pluck('id')->all();
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

    public function toArray()
    {
        $array = parent::toArray();

        $array['order_ids'] = $this->order_ids;
        if ($this->customer_emails) {
            $array['customer_emails'] = $this->customer_emails;
        }

        return $array;
    }

    /**
     * @return HasMany
     */
    public function order_customer_types(): HasMany
    {
        return $this->hasMany(OrderCustomerType::class, 'customer_id', 'customers_id');
    }
}
