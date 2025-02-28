<?php

namespace App\Lib\ModuleHandlers\Interfaces;

use App\Exceptions\ModuleHandlerException;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface ModuleHandlerInterface
 * @package App\Lib\Interfaces
 */
interface ModuleHandlerInterface
{
    /**
     * @return void
     */
    public function performAction(): void;

    /**
     * @throws Module|null
     */
    public function getResource(): ?Model;

    /**
     * @throws ModuleHandlerException
     * @return int|null
     */
    public function getResourceId(): ?int;
}
