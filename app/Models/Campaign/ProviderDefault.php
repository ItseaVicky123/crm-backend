<?php

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Model;

class ProviderDefault extends Model
{
    const CREATED_AT = 'date_in';
    const UPDATED_AT = null;

    protected $table = 'campaign_provider_default';

    protected $fillable = [
        'provider_type_id',
        'profile_id',
        'admin_id',
    ];
}