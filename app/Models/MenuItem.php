<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class MenuItem
 * Reader for the v_menu_items view, uses slave connection.
 * @package App\Models
 */
class MenuItem extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    public $table = 'v_menu_items';

    /**
     * @var string
     */
    public $perPage = '500';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'order',
        'link',
        'children',
        'roles',
        'parent_id',
    ];

    protected $with = [
        'children',
    ];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('menu_item_visible', function (Builder $builder) {
            $builder->where('is_visible', 1)
                ->orderBy('order');

            if (! \is_superuser()) {
                $builder->where('is_admin_page', 0);
            };
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roles()
    {
        return $this->hasMany(MenuRole::class, 'mid', 'id');
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeParentsOnly(Builder $query)
    {
        return $query->where('parent_id', 0);
    }
}
