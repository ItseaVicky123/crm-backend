<?php

namespace App\Models;

use App\Models\NotificationEventTypes\Order\ConsentReceived;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

/**
 * Class OrderConsent
 * @package App\Models
 */
class OrderConsent extends BaseModel
{

    /**
     * @var string
     */
    protected $table = 'order_consent';
    protected $primaryKey = 'order_id';
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'order_id',
        'ip_address',
        'api_user_id',
        'user_id',
        'http_referrer',
        'created_at',
        'request_headers',
        'order_consent_type',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'created_at',
        'order_id',
        'ip_address',
        'api_user_id',
        'user_id',
        'http_referrer',
        'request_headers',
        'order_consent_type_id',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($consent)
        {
            $request = Request::instance();

            if ($headers = $request->headers->all()) {
                $consent->request_headers = json_encode($headers);
            }
        });

        static::created(function ($consent)
        {
            $admin_id = \history_note_base::LIME_LIGHT_API_ADMIN;
            $note     = 'Consent received';

            if ($user = $consent->api_user_id) {
                $apiUser = ApiUser::find($user);
                $note    .= " from API User {$apiUser->username}";
            } else {
                $admin_id = $consent->user_id;
            }

            new \history_note($consent->order_id, $admin_id, 'history-note-consent-received', $note);

            if (!\system_module_control::check('USE_CONSENT_SERVICE')) {
                \AsyncProcess('SendOrderNotificationQueue', \BuildQueryString([
                    'order_id'      => $consent->order_id,
                    'event_type_id' => ConsentReceived::TYPE_ID,
                    'status_id'     => '',
                ]));
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'orders_id');
    }

    /**
     * @return mixed
     */
    public function getRequestHeadersAttribute()
    {
        return json_decode($this->attributes['request_headers'], true);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order_consent_type()
    {
        return $this->hasOne(OrderConsentType::class);
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    public function getOrderConsentTypeAttribute()
    {
        return $this->order_consent_type()->first();
    }

    /**
     * @param $order_id
     */
    public static function forceBillCreate($order_id)
    {
        $order = Order::find($order_id);

        if ($order->is_consent_required && ! $order->has_consent) {
            self::create([
                'order_id'              => $order->id,
                'ip_address'            => \get_ip_address_from_post(),
                'order_consent_type_id' => OrderConsentType::TYPE_ID_CALL,
            ]);
        }
    }

    /**
     * @param Builder $query
     * @param         $orderId
     * @return Builder
     */
    public function scopeForOrder(Builder $query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }
}
