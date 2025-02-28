<?php

namespace App\Models;

use App\Traits\ModelImmutable;
use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence;

/**
 * Class TaxExemption
 * @package App\Models
 */
class TaxExemption extends Model
{
    use Eloquence, ModelImmutable;

    /**
     * @var bool
     */
    public $timestamps = false;
}
