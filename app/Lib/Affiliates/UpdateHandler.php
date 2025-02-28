<?php


namespace App\Lib\Affiliates;

use Illuminate\Database\Eloquent\Model;
use App\Models\Affiliates\Affiliate;

/**
 * Handle updating an existing affiliate.
 * Class UpdateHandler
 * @package App\Lib\Affiliates
 */
class UpdateHandler extends SaveHandler
{
    /**
     * @var bool $isUpdateExisting
     */
    protected bool $isUpdateExisting = true;

    /**
     * Create an instance of Affiliate.
     * @return Model
     */
    protected function generateResource(): Model
    {
        $resource = Affiliate::findOrFail($this->moduleRequest->id);
        $updates  = [];

        if ($this->moduleRequest->has('type_id')) {
            $updates['type_id'] = $this->moduleRequest->type_id;
        }

        if ($this->moduleRequest->has('value')) {
            $updates['value'] = $this->moduleRequest->value;
        }

        if ($this->moduleRequest->has('network')) {
            $updates['network'] = $this->moduleRequest->network;
        }

        if ($updates) {
            $resource->update($updates);
        }

        return $resource;
    }
}
