<?php


namespace App\Lib\Affiliates;

use Illuminate\Database\Eloquent\Model;
use App\Lib\ModuleHandlers\ModuleHandler;
use App\Models\Affiliates\Affiliate;
use App\Exceptions\ModuleHandlers\ModuleHandlerException;

/**
 * Handle the creation of affiliates.
 * Class SaveHandler
 * @package App\Lib\Affiliates
 */
class SaveHandler extends ModuleHandler
{
    /**
     * Create the affiliate.
     * @throws ModuleHandlerException
     */
    public function performAction(): void
    {
        if ($this->resource = $this->generateResource()) {
            $this->resourceId = $this->resource->id;
            $this->resource->refresh();
        } else {
            throw new ModuleHandlerException(__METHOD__, 'affiliates.create-resource-failed');
        }
    }

    /**
     * Define validation rules for affiliate save API methods.
     */
    protected function beforeValidation(): void
    {
        $isCreating            = !$this->isUpdateExisting;
        $required              = $isCreating ? 'required|' : '';
        $this->validationRules = [
            'type_id' => "{$required}int|min:1|exists:mysql_slave.v_affiliate_types,id",
            'value'   => "{$required}string|min:1|max:255",
            'network' => 'string|min:1|max:255',
        ];
        $this->friendlyAttributeNames = [
            'type_id' => 'Affiliate Type ID',
            'value'   => 'Affiliate Value',
            'network' => 'Affiliate Network Name',
        ];
    }

    /**
     * Create an instance of Affiliate.
     * @return Model
     */
    protected function generateResource(): Model
    {
        return Affiliate::create([
            'type_id' => $this->moduleRequest->type_id,
            'value'   => $this->moduleRequest->value,
            'network' => $this->moduleRequest->get('network', ''),
        ]);
    }
}
