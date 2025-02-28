<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class EmailEditorVersion
 * Reader for the v_ckeditor_version view, uses slave connection.
 * @package App\Models
 */
class EmailEditorVersion extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    /**
     * @var string
     */
    protected $table = 'v_ckeditor_version';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'version',
    ];
}
