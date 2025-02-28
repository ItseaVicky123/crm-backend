<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use App\Traits\ModelImmutable;

/**
 * Class IntercomUserAttribute
 * Reader for the v_intercom_user_attributes view, uses slave connection.
 * @package App\Models
 */
class IntercomUserAttribute extends Model
{
    use Eloquence, ModelImmutable;

    const ROLE_ID_CHAT = 300;
    const PROD_USER_HASH = 's39mytHgt3bnWLNenvgpxzt37CZyuATyY6oE9dF6';
    const DEV_APP_ID     = 'vo87w2dl';
    const DEV_USER_HASH  = 'ttSiKDZEM5G6eXVuQ2aTLUjqYG5O_QCeX1dmAD7w';

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_intercom_user_attributes';

    /**
     * @param Builder $query
     * @param         $value
     * @return Builder
     */
    public function scopeForUserKey(Builder $query, $value)
    {
        return $query->where('user_id', $value);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $return = parent::toArray();

        if ($return) {
            $userHash = self::PROD_USER_HASH;

            if (ENVIRONMENT_TYPE === ENV_TYPE_DEV) {
                $userHash         = self::DEV_USER_HASH;
                $return['app_id'] = self::DEV_APP_ID;
            }

            $return = array_merge($return, [
                'company'                  => json_decode($return['company'], true),
                'custom_launcher_selector' => '.js-intercom-launcher',
                'hide_default_launcher'    => ! checkRole(self::ROLE_ID_CHAT),
                'user_hash'                => hash_hmac(
                    'sha256',
                    $return['user_id'],
                    $userHash
                ),
            ]);
        }

        return $return;
    }
}
