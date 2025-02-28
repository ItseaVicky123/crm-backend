<?php

namespace App\Models\Payment;

use App\Models\Address;
use App\Models\Binbase;
use App\Models\Contact\Contact;
use App\Models\User;
use App\Lib\HasCreator;
use App\Lib\Encryption\PaymentHash;
use App\Lib\Encryption\PaymentSource;
use App\Exceptions\UnknownCardBrandException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Sofa\Eloquence\Eloquence;
use App\Traits\HasUuidFind;

/**
 * Class ContactPaymentSource
 * @package App\Models\Payment
 */
class ContactPaymentSource extends Model
{
    use SoftDeletes, Eloquence, HasCreator, HasUuidFind;

    const CREATED_BY = 'created_by';

    const UPDATED_BY = 'updated_by';

    /**
     * @var array
     */
    protected $fillable = [
        'account_number',
        'alias',
        'expiry',
        'is_default',
        'payment_type_id',
        'payment_method_id',
        'address_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'uuid',
        'alias',
        'expiry',
        'is_default',
        'first_6',
        'last_4',
        // Appends
        'expiry_month',
        'expiry_year',
        'address',
        'payment_type',
        'payment_method',
        'creator',
        'updator',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'last_4',
        'creator',
        'updator',
        'expiry_month',
        'expiry_year',
    ];

    /**
     * @var array
     */
    protected $with = [
        'address',
        'payment_type',
        'payment_method',
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
    protected $casts = [
        'first_6' => 'string',
        'last_4'  => 'string',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($contactPaymentSource) {
            $user                               = get_current_user_id();
            $contactPaymentSource->payment_hash = PaymentHash::hash($contactPaymentSource->account_number);

            if (! $contactPaymentSource->created_by) {
                $contactPaymentSource->created_by = $user;
            }

            if ($contactPaymentSource->payment_type_id == PaymentType::TYPE_CREDIT_CARD) {
                $payment_method_id             = null;
                $method                        = null;
                $contactPaymentSource->first_6 = PaymentSource::get_first_six_from_cc($contactPaymentSource->account_number);
                $contactPaymentSource->last_4  = PaymentSource::get_last_four_from_cc($contactPaymentSource->account_number);
                $bin                           = Binbase::where('bin', '=', $contactPaymentSource->first_6)->first();

                if ($bin) {
                    $method = $bin->payment_method()->first();
                }

                if ($method) {
                    $payment_method_id = $method->id;
                } elseif (\isTestCC(['ccNumber' => $contactPaymentSource->account_number, 'updateCountFlag' => false])) {
                    $payment_method_id = PaymentMethod::creditCards()
                        ->where('name', '=', 'visa') // cuz why not
                        ->first()
                        ->id;
                }

                if ($payment_method_id) {
                    $contactPaymentSource->payment_method_id = $payment_method_id;
                } else {
                    throw new UnknownCardBrandException(
                        "Brand was not found. Bin: {$contactPaymentSource->first_6}, Brand: " . ($bin->brand ?? ''),
                        404
                    );
                }
            }

            if (! $contactPaymentSource->uuid) {
                $contactPaymentSource->uuid = Str::uuid();
            }
        });

        static::updating(function ($contactPaymentSource) {
            if (! $contactPaymentSource->updated_by) {
                $contactPaymentSource->updated_by = \current_user(User::SYSTEM);
            }
        });

        static::deleting(function ($contactPaymentSource) {
            $contactPaymentSource->is_default = null;
        });
    }

    /**
     * @return int
     */
    public function getIsDefaultAttribute()
    {
        return $this->attributes['is_default'] ?? 0;
    }

    /**
     * @param $value
     */
    public function setAccountNumberAttribute($value)
    {
        $this->attributes['account_number'] = PaymentSource::encrypt($value);
    }

    /**
     * @return mixed|string
     */
    public function getAccountNumberAttribute()
    {
        return PaymentSource::decrypt($this->attributes['account_number']);
    }

    /**
     * @return mixed|string
     */
    public function getAccountNumberEncryptedAttribute()
    {
        return $this->attributes['account_number'];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment_type()
    {
        return $this->belongsTo(PaymentType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return bool|string
     */
    public function getExpiryMonthAttribute()
    {
        return substr($this->attributes['expiry'], 0, 2);
    }

    /**
     * @return bool|string
     */
    public function getExpiryYearAttribute()
    {
        return substr($this->attributes['expiry'], -2);
    }

    /**
     * @return string
     */
    public function getLast4Attribute()
    {
        return str_pad($this->attributes['last_4'], 4, '0', STR_PAD_LEFT);
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
