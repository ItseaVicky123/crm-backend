<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderBatchId
 * @package App\Models
 */
class OrderBatch extends Model
{
    /**
     * @var string
     */
    protected $table = 'order_batch_ids';

    /**
     * @var string
     */
    protected $primaryKey = 'ordersBatchId';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'inProgress',
        'createdOn',
        'startedOn',
        'completedOn',
        'outputFileName',
        'inputFileName',
        'importType',
        'importSubType',
        'campaignFilter',
        'name',
        'email',
        'saved_file_hash',
        'saved_file_path',
        'rows_to_process',
        'rows_processed',
        'cloud_flag',
        'admin_id',
        'batch_id',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'inProgress',
        'createdOn',
        'startedOn',
        'completedOn',
        'outputFileName',
        'inputFileName',
        'importType',
        'importSubType',
        'campaignFilter',
        'name',
        'email',
        'saved_file_hash',
        'saved_file_path',
        'rows_to_process',
        'rows_processed',
        'cloud_flag',
        'admin_id',
        'batch_id',
    ];

    public const CREATED_AT = 'createdOn';

    /**
     * @var array
     */
    protected $dates = [
        self::CREATED_AT,
    ];
}
