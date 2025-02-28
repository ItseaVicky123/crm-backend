<?php

namespace App\Models\SWS;

use App\Models\Campaign\Campaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use InvalidArgumentException;


/**
 * Class InitialDunningProfileRule
 *
 * @property int $id
 * @property int $initial_dunning_profile_id
 * @property string $type
 * @property int $ruleable_id
 * @property string $ruleable_type
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property InitialDunningProfile $initialDunningProfile
 *
 * @method static Builder|InitialDunningProfileRule where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder|InitialDunningProfileRule whereNotIn(string $column, array $values, string $boolean = 'and')
 * @method static InitialDunningProfileRule findOrFail(int $id)
 * @method static InitialDunningProfileRule create(array $attributes)
 */
class InitialDunningProfileRule extends Model
{
    const VALID_TYPES = [
        'campaign' => Campaign::class,
    ];
//    const VALID_TYPES = ['campaign', 'product', 'decline_reason', 'card_type', 'gateway', 'payment_method']; Only campaign for now, keeping this for the future

    protected $fillable = ['initial_dunning_profile_id', 'type', 'ruleable_id', 'ruleable_type'];

    public function initialDunningProfile(): BelongsTo
    {
        return $this->belongsTo(InitialDunningProfile::class);
    }

    public function ruleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function setTypeAttribute(string $value)
    {
        if (!array_key_exists($value, self::VALID_TYPES)) {
            throw new InvalidArgumentException('Invalid type value');
        }
        $this->attributes['type'] = $value;
    }
}