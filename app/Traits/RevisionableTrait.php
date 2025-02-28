<?php

namespace App\Traits;

use Illuminate\Support\Arr;
use Venturecraft\Revisionable\RevisionableTrait as BaseRevisionableTrait;

trait RevisionableTrait
{
    use BaseRevisionableTrait;

    /**
     * @return array
     */
    public function getAdditionalFields(): array
    {
        $additional = [];
        if (!empty($this->orderId)) {
            Arr::set($this->originalData, 'order_id', $this->orderId);
            $additional['order_id'] = $this->orderId;
        }
        if (!empty($this->orderType)) {
            Arr::set($this->originalData, 'order_type', $this->orderType);
            $additional['order_type'] = $this->orderType;
        }

        return $additional;
    }

    /**
     * This function is for revisionable to record user id for audit
     * @return mixed|null
     */
    public function getSystemUserId()
    {
        return get_current_user_id();
    }
}
