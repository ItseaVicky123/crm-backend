<?php

namespace App\Models\SWS;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Class InitialDunningProfile
 *
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property int $response_code
 * @property int $priority
 * @property bool $with_cvv_first_24
 * @property bool $without_cvv_after_24
 * @property int $dunning_window_days
 * @property int $max_attempt_number
 * @property int $sws_profile_id
 * @property string $filter_decline_reasons
 * @property bool $apply_to_all_campaigns
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static InitialDunningProfile find(int $id)
 * @method static InitialDunningProfile findOrFail(int $id)
 * @method static Builder where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static int max(string $column)
 * @method static InitialDunningProfile create(array $attributes)
 * @method static int count()
 * @method static Collection each(callable $callback)
 */
class InitialDunningProfile extends Model
{
    use SoftDeletes;

    const FILTER_DECLINE_REASONS_BLOCK    = 'block';
    const FILTER_DECLINE_REASONS_ALLOW    = 'allow';
    const FILTER_DECLINE_REASONS_DISABLED = 'disabled';

    protected $fillable = [
        'name',
        'active',
        'response_code',
        'priority',
        'with_cvv_first_24',
        'without_cvv_after_24',
        'dunning_window_days',
        'max_attempt_number',
        'sws_profile_id',
        'filter_decline_reasons',
        'apply_to_all_campaigns'
    ];

    protected $casts = [
        'active'                 => 'boolean',
        'response_code'          => 'integer',
        'with_cvv_first_24'      => 'boolean',
        'without_cvv_after_24'   => 'boolean',
        'dunning_window_days'    => 'integer',
        'max_attempt_number'     => 'integer',
        'sws_profile_id'         => 'integer',
        'apply_to_all_campaigns' => 'boolean'
    ];

    protected $attributes = [
        'filter_decline_reasons' => 'disabled',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(InitialDunningProfileRule::class);
    }
}