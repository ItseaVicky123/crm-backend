<?php

namespace App\Lib\Blacklist;

use App\Lib\Blacklist\ModuleRequests\SaveRequest;
use App\Lib\Blacklist\ModuleRequests\UpdateRequest;
use App\Lib\Blacklist\ModuleRequests\SaveRuleDetailRequest;
use App\Lib\Blacklist\ModuleRequests\UpdateRuleDetailRequest;
use App\Lib\Blacklist\ModuleRequests\BlacklistRequest;
use App\Lib\Blacklist\ModuleRequests\BlacklistDetailRequest;
use App\Models\Blacklist\BlacklistRule;
use App\Models\Blacklist\BlacklistRuleDetail;
use App\Models\Blacklist\BlacklistBinNumber;
use App\Lib\Traits\HasBlacklist;

/**
 * Class BlacklistHandler
 *
 * @package App\Lib\Blacklist
 */
class BlacklistHandler
{
    use HasBlacklist;

    /**
     * Create a blacklist rule with a pre-validated request.
     *
     * @param SaveRequest $request
     * @return Blacklist
     */
    public function create(SaveRequest $request): BlacklistRule
    {

        $resource = BlacklistRule::create([
            'name'        => $request->name,
            'description' => $request->description,
            'status'      => $request->get('status', 1),
        ]);

        $resource->refresh();

        return $resource;
    }

    /**
     * Updated a blacklist rule with a pre-validated request.
     *
     * @param UpdateRequest $request
     * @return Blacklist
     */
    public function update(UpdateRequest $request): BlacklistRule
    {
        $resource = $request->getBlacklist();
        $updates  = [
            'name'        => $request->name,
            'description' => $request->description,
            'status'      => $request->status,
        ];

        $resource->update($updates);
        if (count($resource->rule_details)) {
            BlacklistRuleDetail::where('rule_id', $resource->id)->update([
                'rule_status' => $updates['status'],
                'updated_by'  => get_current_user_id(),
            ]);
        }

        $resource->refresh();

        return $resource;
    }

    /**
     * Copy an existing blacklist rule.
     *
     * @param BlacklistRequest $request
     * @return BlacklistRule
     */
    public function copy(BlacklistRequest $request): BlacklistRule
    {
        $resource = $request->getBlacklist();
        $copy     = BlacklistRule::create([
            'name'        => $resource->name.' (Copy)',
            'description' => $resource->description,
            'status'      => 1,
        ]);

        $copy->refresh();

        return $copy;
    }

    /**
     * Delete a blacklist rule.
     *
     * @param BlacklistRequest $request
     * @return bool
     * @throws \Exception
     */
    public function destroy(BlacklistRequest $request): bool
    {
        $blacklistRule = $request->getBlacklist();
        if (! empty($blacklistRule->rule_details)) {
            foreach ($blacklistRule->rule_details as $rule_detail) {
                $this->destroyRuleDetails(new BlacklistDetailRequest(['id' => $rule_detail['id']]));
            }
        }

        return (bool) $blacklistRule->delete();
    }

    /**
     * Create a blacklist rule detail with a pre-validated request.
     *
     * @param SaveRuleDetailRequest $request
     * @return BlacklistRuleDetail
     */
    public function createRuleDetails($id, SaveRuleDetailRequest $request): BlacklistRuleDetail
    {
        $this->validateRuleDetailsRequest($request);

        $resource = BlacklistRuleDetail::create($this->getValidRuleDetailDataToSave($request));

        if ($resource->id && $request->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_MULTIPLE) {
            foreach ($request->values as $value) {
                if (! empty($value)) {
                    BlacklistBinNumber::create(['rule_detail_id' => $resource->id, 'value' => $value]);
                }
            }
        }

        $resource->refresh();

        return $resource;
    }

    /**
     * Updated a blacklist rule detail with a pre-validated request.
     *
     * @param UpdateRuleDetailRequest $request
     * @return BlacklistRuleDetail
     */
    public function updateRuleDetails(int $detailId, UpdateRuleDetailRequest $request): BlacklistRuleDetail
    {
        $this->validateRuleDetailsRequest($request);

        $validData = $this->getValidRuleDetailDataToSave($request, $detailId);

        BlacklistRuleDetail::where('id', $detailId)->update($validData);

        if ($request->criteria_type == BlacklistRuleDetail::CRITERIA_TYPE_MULTIPLE) {
            BlacklistBinNumber::where('rule_detail_id', $detailId)->delete();
            foreach ($request->values as $value) {
                if (! empty($value)) {
                    BlacklistBinNumber::create(['rule_detail_id' => $detailId, 'value' => $value]);
                }
            }
        }

        return BlacklistRuleDetail::findOrFail($detailId);
    }

    /**
     * Delete a blacklist rule detail.
     *
     * @param BlacklistDetailRequest $request
     * @return bool
     * @throws \Exception
     */
    public function destroyRuleDetails(BlacklistDetailRequest $request): bool
    {
        $blacklistRuleDetail = $request->getBlacklistDetail();
        if ($blacklistRuleDetail) {
            BlacklistActions::deleteBlacklistRuleDetails([$blacklistRuleDetail->id]);
        }

        return (bool) $blacklistRuleDetail->delete();
    }
}
