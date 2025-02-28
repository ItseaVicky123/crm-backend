<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelImmutable;

/**
 * Class OrderLinkType
 * Reader for the v_page view, uses slave connection.
 * @package App\Models
 */
class Page extends Model
{
    use ModelImmutable;

    protected $connection = \App\Models\BaseModel::SLAVE_CONNECTION;


    protected $table = 'v_page';
}
