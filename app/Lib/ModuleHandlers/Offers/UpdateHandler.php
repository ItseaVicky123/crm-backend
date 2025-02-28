<?php

namespace App\Lib\ModuleHandlers\Offers;

use Illuminate\Database\Eloquent\Model;
use App\Models\Offer\Offer;

/**
 * Class UpdateHandler
 * @package App\Lib\ModuleHandlers\Offers
 */
class UpdateHandler extends SaveHandler
{
    /**
     * @var bool $isUpdateExisting
     */
    protected bool $isUpdateExisting = true;

    /**
     * Fetch the offer resource.
     * @return Model
     */
    protected function generateResource(): Model
    {
        $resource = Offer::findOrFail($this->moduleRequest->id);

        if ($this->moduleRequest->has('name')) {
            $resource->update(['name' => $this->moduleRequest->name]);
        }

        return $resource;
    }
}
