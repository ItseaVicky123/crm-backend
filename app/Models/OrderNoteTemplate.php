<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;
use App\Lib\Lime\LimeSoftDeletes;

/**
 * Class OrderNoteTemplate
 * @package App\Models
 */
class OrderNoteTemplate extends Model
{
    use Eloquence, Mappable, LimeSoftDeletes;

    /**
     * @var string
     */
    protected $table = 'order_note_template';

    /**
     * @var int
     */
    public $perPage = 100;

    /**
     * @var array
     */
    protected $maps = [
        'name'        => 'note_label',
        'content'     => 'note_content',
        'is_editable' => 'editable_flag',
        'is_global'   => 'all_campaigns_flag',
        'is_deleted'  => 'deleted',
        'is_active'   => 'active',
        'updated_by'  => 'modified_by',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'content',
        'is_editable',
        'is_active',
        'is_global',
        'updated_by',
        'created_by',
        'type_id',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'is_global',
        'is_editable',
        'campaigns',
        'content',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'name',
        'is_global',
        'is_editable',
        'campaigns',
        'content',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns()
    {
        return $this->hasMany(OrderNoteTemplateCampaign::class, 'note_profile_id', 'id');
    }

    /**
     * @return array
     */
    protected function getCampaignsAttribute()
    {
        return $this->campaigns()
            ->get()
            ->pluck('campaign_id')
            ->toArray();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function type()
    {
        return $this->hasOne(OrderNoteTemplateType::class, 'id', 'type_id');
    }

    /**
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasOne|object|null
     */
    protected function getTypeAttribute()
    {
        return $this->type()->first();
    }

    /**
     * Is my template of the default type?
     *
     * @return bool
     */
    public function isDefaultType(): bool
    {
        return $this->type->isDefaultType();
    }

    /**
     * Get the 'type' template string used for insertion into orders_history
     *
     * @return string
     */
    public function getHistoryTypeString(): string
    {
        if ($this->isDefaultType()) {
            return OrderHistoryNoteType::TYPE_NOTES;
        } else {
            return "history-note-template-{$this->id}-{$this->type->id}";
        }
    }

}
