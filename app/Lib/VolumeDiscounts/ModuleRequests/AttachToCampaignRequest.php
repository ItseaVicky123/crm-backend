<?php


namespace App\Lib\VolumeDiscounts\ModuleRequests;

use App\Lib\ModuleHandlers\ModuleRequest;
use Illuminate\Validation\ValidationException;
use App\Lib\Traits\HasVolumeDiscount;

/**
 * Class AttachToCampaignRequest
 * @package App\Lib\VolumeDiscounts\ModuleRequests
 */
class AttachToCampaignRequest extends ModuleRequest
{
    use HasVolumeDiscount;

    /**
     * AttachToCampaignRequest constructor.
     * @param array $data
     * @throws ValidationException
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $rules = [
            'id'               => 'required|int|exists:mysql_slave.volume_discounts,id',
            'is_replace'       => 'bool',
            'campaigns'        => 'required_without:is_all_campaigns|array',
            'campaigns.*.id'   => 'required_with:campaigns|int',
            'is_all_campaigns' => 'bool|required_without:campaigns',
        ];
        $attributes = [
            'id'               => 'Volume Discount ID',
            'is_replace'       => 'Replace Campaign Relationships Flag',
            'campaigns'        => 'Campaigns',
            'campaigns.*.id'   => 'Campaign ID',
            'is_all_campaigns' => 'All Campaigns Flag',
        ];
        $this->validate($rules, $attributes);
        $this->setVolumeDiscount($this->id);
    }
}
