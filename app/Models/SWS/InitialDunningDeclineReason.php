<?php

namespace App\Models\SWS;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class InitialDunningDeclineReason
 *
 * @property int $initial_dunning_profile_id
 * @property string $decline_reason
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static Builder|InitialDunningDeclineReason where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder|InitialDunningDeclineReason whereRaw($sql, $bindings = [], $boolean = 'and')
 */
class InitialDunningDeclineReason extends Model
{
    protected $primaryKey = ['initial_dunning_profile_id', 'decline_reason'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'initial_dunning_profile_id',
        'decline_reason',
    ];
}