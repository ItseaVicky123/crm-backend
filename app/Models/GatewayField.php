<?php

namespace App\Models;

use App\Traits\ModelReader;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use Illuminate\Support\Facades\DB;

/**
 * Class GatewayField
 * @package App\Models
 */
class GatewayField extends Model
{
    use Eloquence, Mappable, ModelReader;

    const CREATED_AT = 'createdOn';
    const UPDATED_AT = 'updatedOn';

    public const CAPTURE_ON_FULFILLMENT_POST = 'Capture On Fulfillment Post';

    public const RETRY_EXPIRED_AUTH          = 'Retry Expired Authorization';

    public const AUTO_SHIP_ON_REBILLS        = 'Auto Ship on Rebills';

    public const USE_V0_ENDPOINT             = 'Use V0 Endpoint';

    /**
     * @var string
     */
    protected $primaryKey = 'gatewayFieldsId';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'gateway_id',
        'key',
        'value',
    ];

    /**
     * @var array
     */
    protected $maps = [
        'id'         => 'gatewayFieldsId',
        'gateway_id' => 'gatewayId',
        'name'       => 'fieldName',
        'key'        => 'fieldName',
        'value'      => 'fieldValue',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'id',
        'gateway_id',
        'key',
        'value',
    ];

    /**
     * DO NOT CHANGE
     * Unable to use $guarded because of maps
     * @var string[]
     */
    protected $fillable = [
        'gateway_id',
        'key',
        'value',
        'name',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function profile()
    {
        return $this->belongsTo(GatewayProfile::class, 'gatewayId');
    }

    /**
     * @return bool|mixed
     */
    protected function getFieldValueAttribute()
    {
        $value = $this->attributes['fieldValue'];

        if (in_array($value, [1, 'yes', 'Yes'], true)) {
            return true;
        } elseif (in_array($value, [0, 'no', 'No'], true)) {
            return false;
        }

        return $value;
    }

    /**
     * Check if specific setting is enabled
     *
     * @param $gatewayId
     * @param string $fieldName
     * @return bool
     */
    public static function isEnabled($gatewayId, string $fieldName): bool
    {
        return self::isExists($gatewayId, $fieldName, 'yes');
    }

    /**
     * Check if specific setting exist with specified value
     *
     * @param $gatewayId
     * @param string $fieldName
     * @param string $fieldValue
     * @return bool
     */
    public static function isExists($gatewayId, string $fieldName, string $fieldValue): bool
    {
        return self::where('gateway_id', $gatewayId)
            ->where('fieldName', $fieldName)
            ->where('fieldValue', $fieldValue)
            ->exists();
    }

    /**
     * Define a relationship to the Image model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function images()
    {
        return $this->belongsTo(Image::class, 'fieldValue', 'id');
    }

    /**
     * Get the Field by gatewayId and fieldName.
     *
     * @param int $gatewayId
     * @param string $fieldName
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function getImageByGateway(int $gatewayId, string $fieldName)
    {
        return self::where('gateway_id', $gatewayId)
            ->where('fieldName', $fieldName)
            ->with('images')
            ->first();
    }

    /**
     * Get all Gateway Fields (images) by gateway_id and fieldNames.
     *
     * @param int $gatewayId
     * @param array $fieldNames
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllImagesByGateway(int $gatewayId, array $fieldNames)
    {
        return self::where('gateway_id', $gatewayId)
            ->whereIn('fieldName', $fieldNames)
            ->with('images')
            ->get();
    }

    /**
     * Delete the old image record from cloud storage and the database.
     *
     * @param \App\Models\GatewayField $gatewayField
     * @return bool
     */
    public static function deleteOldImage($gatewayField): bool
    {
        try {
            if ($gatewayField->images && ! $gatewayField->images->forceDelete()) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete old image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete both the image record and its associated image.
     *
     * @param \App\Models\GatewayField $gatewayField
     * @return bool
     */
    public static function deleteGatewayFieldAndImage($gatewayField): bool
    {
        DB::beginTransaction();

        try {
            if (!self::deleteOldImage($gatewayField)) {
                throw new \Exception('Failed to delete the associated image.');
            }

            $data = [
                'gatewayFieldsId' => $gatewayField->id,
                'value'           => NULL,
            ];

            if (! $gatewayField->update($data)) {
                throw new \Exception('Failed to delete the image record.');
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete the image: " . $e->getMessage());
            return false;
        }
    }
}
