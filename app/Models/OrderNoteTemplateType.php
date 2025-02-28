<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class OrderNoteTemplateType
 * @package App\Models
 */
class OrderNoteTemplateType extends Model
{
    use Eloquence;

    const DEFAULT = 1;
    const REFUND = 2;
    const CANCEL = 3;
    const SUBSCRIPTION_CREDIT = 4;
    const RESTART_RESET_SUBSCRIPTION = 5;

    /**
     * @var string
     */
    public $table = 'vlkp_note_template_type';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'description',
    ];

    /**
     * Am I of the default type?
     *
     * @return bool
     */
    public function isDefaultType(): bool
    {
        return $this->id === static::DEFAULT;
    }
}
