<?php

namespace App\Traits;

use App\Models\BaseModel;

/**
 * Trait ModelReader
 * @package App\Traits
 */
trait ModelWriter
{
    /**
     * This function is intended to be used on the model or relationship itself (not query)
     * loaded on reader connection to force to use writer
     *
     * @return self
     */
    public function useWriter(): self
    {
        return $this->setConnection(BaseModel::WEB_CONNECTION);
    }
}
