<?php
/**
 * This class is used in the dev and qa environments to
 *  - inject test values for specifically targeted code paths
 *
 * @TODO Implement remaining features via https://sticky.atlassian.net/browse/DEV-371
 *       - trigger failures in specifically targeted code paths
 *       - force successes in specifically targeted code paths
 */
namespace App\Lib\Development;

use Illuminate\Support\Arr;

class QaOrderAssassin
{
    /**
     * The name of the cookie whose value is used to configure the Order Assassin
     */
    public const ORDER_ASSASSIN_COOKIE = 'order-assassin';

    /**
     * The name of the key inside the Order Assassin configuration that specifies values that should be replaced
     * for a targeted code path
     */
    public const OVERRIDE_TARGET_KEY   = 'overrides';

    /**
     * String targets used to identify specific places in the code
     * When adding a new target developers MUST document its usage via
     * https://sticky.atlassian.net/wiki/spaces/ENG/pages/165445639/Using+the+Order+Assassin#Assassin-Targets
     */
    public const TARGET_PAYSAFE_CONSENT_STATUS_RESPONSE = 'paysafe-consent-status-response';
    public const TARGET_PAYSAFE_AUTH_RESPONSE           = 'paysafe-consent-auth-response';
    public const TARGET_CAPTURE_ERROR_RESPONSE          = 'capture-error-response';

    /**
     * @var array|null
     */
    private ?array $config = null;

    /**
     * Override keys in the given $values array of optionally replace the whole array with data supplied from QA/DEV
     * input via order assassin configuration cookie
     *
     * @param string $target  One of the TARGET_X constants
     * @param array  $values  Values from the normal code path to be potentially overridden
     * @param bool   $replace True if the whole array should be replaced, false to only override specified keys
     * @return array
     */
    public function overrideTarget(string $target, array $values, ?bool $replace = false): array
    {
        $overrides       = $this->getConfig(self::OVERRIDE_TARGET_KEY);
        $targetOverrides = is_array($overrides) ? $overrides[$target] ?? null : null;
        if ($targetOverrides) {
            // override the original $values
            if (is_array($overrides) && is_array($values)) {
                // @TODO update to handle non arrays with https://sticky.atlassian.net/browse/DEV-371
                if ($replace) {
                    $values = $overrides;
                } else {
                    foreach ($targetOverrides as $key => $value) {
                        $values[$key] = $value;
                    }
                }
            }
        }
        return $values;
    }

    /**
     * Get all Order Assassin configs, or the config for a specific key
     * @param null $key
     * @return array|mixed|null
     */
    public function getConfig($key = null) {
        if (!isset($this->config) && isDev()) {
            $config = $_COOKIE[self::ORDER_ASSASSIN_COOKIE];
            if ($config) {
                $config       = json_decode($config, true);
                $this->config = Arr::wrap($config);
            }
        }

        return !$key ? $this->config : $this->config[$key] ?? null;
    }


}
