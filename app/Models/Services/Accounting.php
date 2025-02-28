<?php


namespace App\Models\Services;

use App\Models\AccountingProviderAccount;
use App\Models\Service;
use App\Providers\External\QuickBooksServiceProvider;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Accounting
 * @package App\Models\Services
 */
class Accounting extends Service
{

    const PROVIDER_TYPE = 22;
    const QUICKBOOKS    = 1;

    /**
     * @return HasOne
     */
    public function provider(): HasOne
    {
        return $this->hasOne(AccountingProviderAccount::class, 'id', 'provider_id');
    }

    /**
     * @return bool
     */
    public function afterSave()
    {
        if ($this->hasOAuth() && !$this->hasAccessToken()) {
            return app()->make(QuickBooksServiceProvider::class, ['profile' => $this])
                ->doOAuth2Redirect();
        }

        return parent::afterSave();
    }

    /**
     * @return bool
     */
    protected function hasOAuth()
    {
        switch ($this->provider_id) {
            case self::QUICKBOOKS:
                return true;
            default:
                return false;
        }
    }

    /**
     * @return bool
     */
    protected function hasAccessToken()
    {
        return $this->fields()
            ->whereHas('field', function ($query) {
                $query->where('api_name', 'access_token');
            })
            ->where('value', '!=', '')
            ->exists();
    }

    /**
     * @return bool
     */
    protected function hasRequiredOAuthFields()
    {
        switch ($this->provider_id) {
            case self::QUICKBOOKS:
                return $this->fields()
                    ->whereHas('field', function ($query) {
                        $query->whereIn('api_name', ['client_id', 'client_secret']);
                    })
                    ->where('value', '!=', '')
                    ->exists();
            default:
                return false;
        }
    }

    /**
     * @return string
     */
    public function appendMyProviders()
    {
        if ($this->hasOAuth() && !$this->hasAccessToken() && ($url = getOAuthRedirectUrl(self::PROVIDER_TYPE, $this->provider_id, $this->id))) {
            $companyName = \current_domain::company_name();
            $disabled    = '';
            $help        = '';

            if (!($haveRequiredFieldValues = $this->hasRequiredOAuthFields())) {
                $url      = '';
                $disabled = 'disabled';
                $help     = <<<'HTML'
<span class="help-block">Fill out available fields before attempting to authorize</span>
HTML;
            }

            return <<<HTML
<div style="text-align:center;">
   <button onclick="window.open('{$url}', '_blank');" class="_btn-bs btn btn-primary {$disabled}" id="get_auth_code_link" {$disabled}>Authorize {$companyName}</button>
   {$help}
   <br><br>
</div>
HTML;
        }

        return '';
    }
}
