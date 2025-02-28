<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostbackSubscriptionOrder extends Model
{
    const UPDATED_AT = null;

    /**
     * @var array
     */
    protected $guarded = [
       'id'
    ];

    /**
     * @return mixed
     */
    public function order()
    {
        return $this->hasOne(Order::class);
    }

    /**
     * @return mixed
     */
    public function profile()
    {
        return $this->belongsTo(Postback::class);
    }

    /**
     * @param $order_id
     * @param $postback_id
     * @param $method
     * @param $uri
     * @param $headers
     * @return mixed
     */
    public static function snapshot($order_id, $postback_id, $method, $uri, $headers)
    {
        return self::create([
            'order_id'    => $order_id,
            'postback_id' => $postback_id,
            'snapshot'    => json_encode([
                'METHOD'  => $method,
                 'URI'     => $uri,
                'HEADERS' => $headers,
            ])
        ]);
    }
}
