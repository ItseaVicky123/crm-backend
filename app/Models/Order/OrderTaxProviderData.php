<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Traits\ForOrderScope;

/**
 * Class OrderTaxProviderData
 *
 * @package App\Models\Order
 */
class OrderTaxProviderData extends BaseModel
{
    use ForOrderScope;

    /**
     * @var string
     */
    public $primaryKey = 'order_id';

    /**
     * @var array
     */
    protected $casts = [
        'request'  => 'json',
        'response' => 'json',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'order_id',
        'provider_id', // `-1` is for the custom tax profile, `0` is for non taxable and `greater then 0` stands for provider ID
        'request',
        'response',
    ];

    /**
     * @param int|null $orderId
     * @param int $providerId
     * @param $request
     * @param $response
     * @return static
     */
    public static function upsertData(?int $orderId, int $providerId = 0, $request = null, $response = null): self
    {
        return self::updateOrCreate([
            'order_id' => $orderId,
        ], [
            'provider_id' => $providerId,
            'request'     => $request,
            // Store provider response that was stored into the session
            'response'    => $response,
        ]);
    }
}
