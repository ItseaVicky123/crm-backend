<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CustomerEmail
 * @package App\Models
 */
class CustomerEmail extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'customers_emails';

    /**
     * @var string[]
     */
    protected $fillable = [
        'customers_id',
        'email',
        'is_primary',
    ];

    /**
     * @var mixed
     */
    private $customer;

    /**
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customers_id', 'customers_id');
    }

    /**
     * @return Customer
     */
    public function getCustomerAttribute(): Customer
    {
        return $this->customer()->get()->first();
    }

    /**
     * @return int
     */
    public function getCustomerIdAttribute(): int
    {
        if($customer = $this->customer) {
            return $customer->id;
        }

        return 0;
    }
}
