<?php

namespace App\Models;

use App\Lib\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StagingBatch extends Model
{
    use SoftDeletes, HasCreator;

    const STATUS_NEW = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_COMPLETE = 2;
    const STATUS_ERROR = 3;

    const CREATED_BY = 'created_by';
    const UPDATED_BY = 'updated_by';

    protected $hidden = [
        'id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (! $batch->id_key) {
                $batch->id_key = (string) new \uuid();
            }

            if (! $batch->created_by) {
                $batch->created_by = get_current_user_id();
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany(StagingOrder::class, 'batch_id', 'id');
    }

    /**
     * @return StagingBatch
     */
    public function start()
    {
        return $this->setStatus(self::STATUS_IN_PROCESS);
    }

    /**
     * @return StagingBatch
     */
    public function finish()
    {
        return $this->setStatus(self::STATUS_COMPLETE);
    }

    /**
     * @param $reason
     * @return StagingBatch
     */
    public function kill($reason)
    {
        $this->error = $reason;
        return $this->setStatus(self::STATUS_ERROR);
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status)
    {
        $this->status_id = $status;
        $this->save();

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsNewAttribute()
    {
        return $this->status_id == self::STATUS_NEW;
    }

    /**
     * @return bool
     */
    public function getIsInProcessAttribute()
    {
        return $this->status_id == self::STATUS_IN_PROCESS;
    }

    /**
     * @return bool
     */
    public function getIsCompleteAttribute()
    {
        return $this->status_id == self::STATUS_COMPLETE;
    }

    /**
     * @return bool
     */
    public function getIsInErrorAttribute()
    {
        return $this->status_id == self::STATUS_ERROR;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stats()
    {
        return $this->hasMany(StagingBatchStatistic::class, 'batch_id');
    }

    /**
     * @param $key
     * @param null $value
     */
    public function track($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->track($k, $v);
            }
        } else {
            $stat = StagingBatchStatistic::firstOrNew(['name' => $key]);
            $stat->batch_id = $this->id;
            $stat->value = $value;
            $stat->save();
        }
    }

    /**
     * @param $key
     * @param $increment
     */
    public function trackIncrement($key, $increment)
    {
        $existing = $this->stats()->where('name', $key);

        if (! $existing->exists()) {
            $this->track($key, $increment);
        } else {
            $this->track($key, $existing->first()->value + $increment);
        }
    }

    /**
     * @return array
     */
    public function report()
    {
        $report = [];

        $this->stats->each(function ($stat) use (&$report) {
            $report[$stat->name] = round($stat->value);
        });

        return $report;
    }
}
