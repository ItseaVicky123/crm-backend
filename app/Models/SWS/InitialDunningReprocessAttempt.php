<?php

namespace App\Models\SWS;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Class InitialDunningReprocessAttempt
 *
 * @property int $id
 * @property int $order_id
 * @property int $initial_dunning_profile_id
 * @property string $outcome
 * @property int $attempt_number
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property InitialDunningProfile $initialDunningProfile
 *
 * @method static InitialDunningReprocessAttempt create(array $attributes = [])
 * @method static Builder|InitialDunningReprocessAttempt where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder|InitialDunningReprocessAttempt forOrder(Order $order)
 */
class InitialDunningReprocessAttempt extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_id', 'initial_dunning_profile_id', 'outcome', 'attempt_number'];

    const OUTCOME_APPROVED          = 'APPROVED';
    const OUTCOME_DECLINED          = 'DECLINED';
    const OUTCOME_PENDING           = 'PENDING';
    const OUTCOME_NOT_PENDING       = 'NOT_PENDING';
    const OUTCOME_DUPLICATE         = 'DUPLICATE';
    const OUTCOME_DUPLICATE_DECLINE = 'DUPLICATE_DECLINE';
    const OUTCOME_DELETED           = 'DELETED';
    const OUTCOME_EXPIRED           = 'EXPIRED';

    public static function getValidOutcomes(): array
    {
        return [self::OUTCOME_APPROVED, self::OUTCOME_DECLINED, self::OUTCOME_PENDING, self::OUTCOME_DUPLICATE, self::OUTCOME_NOT_PENDING, self::OUTCOME_DUPLICATE_DECLINE, self::OUTCOME_DELETED, self::OUTCOME_EXPIRED];
    }

    public function setOutcomeAttribute(string $value)
    {
        if (!in_array($value, self::getValidOutcomes())) {
            throw new InvalidArgumentException('Invalid outcome value');
        }
        $this->attributes['outcome'] = $value;
    }

    public function initialDunningProfile(): BelongsTo
    {
        return $this->belongsTo(InitialDunningProfile::class);
    }

    public function scopeForOrder(Builder $query, Order $order): Builder
    {
        return $query->where('order_id', $order->id);
    }
}