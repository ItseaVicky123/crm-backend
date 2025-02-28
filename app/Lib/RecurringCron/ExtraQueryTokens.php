<?php


namespace App\Lib\RecurringCron;

use App\Facades\SMC;
use Illuminate\Support\Collection;

/**
 * Augment the recurring rules mechanism
 * Class ExtraQueryTokens
 * @package App\Lib\RecurringCron
 */
class ExtraQueryTokens extends Collection
{
    /**
     * @var bool $isSalvage
     */
    private bool $isSalvage;

    /**
     * @var array $salvageOnlyKeys
     */
    private array $salvageOnlyKeys = [];

    /**
     * ExtraQueryTokens constructor.
     * @param bool $isSalvage
     */
    public function __construct(bool $isSalvage = false) {
        parent::__construct([]);
        $this->isSalvage = $isSalvage;
    }

    /**
     * @param $key
     * @param string $value
     */
    public function putSalvageToken($key, string $value): void
    {
        if ($this->isSalvage ) {
            $this->putAll($key, $value);
        } else {
            $this->put($key, '');
        }
    }

    /**
     * @param $key
     * @param string $value
     */
    public function putAll($key, string $value): void
    {
        if (! in_array($key, $this->salvageOnlyKeys)) {
            $this->salvageOnlyKeys[] = $key;
        }

        $this->put($key, $value);
    }

    /**
     * @param $key
     * @return string
     */
    public function getSalvageToken($key): string
    {
        if ($this->isSalvageKey($key)) {
            return $this->get($key);
        }

        return '';
    }

    /**
     * @param array $fill
     * @param string $key
     */
    public function appendTo(array &$fill, string $key): void
    {
        $value = '';

        if ($this->isSalvage) {
            $value = $this->getSalvageToken($key);
        } else if ($this->has($key)) {
            $value = $this->get($key);
        }

        $fill[$key] = $value;
    }

    /**
     * @param $fill
     * @param string $key
     * @param bool $isUpsell
     */
    public function applySalvageMainFilter(&$fill, string $key, bool $isUpsell = false): void
    {
        $dunningEnhancementOn = SMC::check(SMC::SMART_DUNNING_ENHANCEMENT);
        $template             = 'AND (attempt_no IS <NOT>NULL <OR>)<EXTRA><UPSELL>';
        $binds                = [
            '<NOT>'    => '',
            '<OR>'     => '',
            '<UPSELL>' => '',
            '<EXTRA>'  => '',
        ];
        $dateNotEquals = '';

        if ($this->isSalvage) {
            $binds['<NOT>'] = 'NOT ';
            $dateNotEquals  = '!';

            if (SMC::check('INITIAL_DUNNING')) {
                $binds['<OR>']  = ' OR idunning.order_id IS NOT NULL';
            }
        }

        if ($isUpsell) {
            $binds['<UPSELL>'] = ' AND uo.date_purchased ' . $dateNotEquals . '= "0000-00-00"';

            if ($dunningEnhancementOn) {
                $binds['<UPSELL>'] .=  ' AND uard.entity_primary_id IS NULL';
            }
        } else if ($dunningEnhancementOn) {
            $binds['<EXTRA>']  = ' AND ard.order_id IS NULL';
        }

        $fill[$key] = strtr($template, $binds);
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isSalvageKey(string $key): bool
    {
        return $this->isSalvage && in_array($key, $this->salvageOnlyKeys) && $this->has($key);
    }
}
