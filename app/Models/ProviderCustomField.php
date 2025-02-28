<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ProviderCustomField
 * @package App\Models
 */
class ProviderCustomField extends Model
{
    use HasCompositePrimaryKey;

    const CREATED_AT = 'created_at';

    /**
     * @var array
     */
    protected $primaryKey = [
        'provider_type_id',
        'account_id',
        'profile_id',
        'name',
    ];

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $visible = [
        'name',
        'value',
        'token_type_id',
        'created_at',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'provider_type_id',
        'token_type_id',
        'account_id',
        'profile_id',
        'name',
        'value',
        'template_attribute_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($providerCustomField) {
            $template = TemplateAttribute::where('name', $providerCustomField->value)
                ->where('type_id', $providerCustomField->token_type_id)
                ->first();

            if ($template) {
                $providerCustomField->template_attribute_id = $template->id;
            }
        });
    }

    /**
     * @param Builder $query
     * @param         $providerTypeId
     * @param         $accountId
     * @param         $profileId
     * @return Builder
     */
    public function scopeForProfile(Builder $query, $providerTypeId, $accountId, $profileId)
    {
        return $query->where('provider_type_id', $providerTypeId)
            ->where('account_id', $accountId)
            ->where('profile_id', $profileId);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function token()
    {
        return $this->hasOne(TemplateAttribute::class, 'id', 'template_attribute_id');
    }
}
