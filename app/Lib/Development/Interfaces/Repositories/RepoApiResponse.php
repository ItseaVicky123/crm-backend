<?php

namespace App\Lib\Development\Interfaces\Repositories;

use Illuminate\Support\Collection;

interface RepoApiResponse
{
    /**
     * Get the data returned by the api call
     * @return Collection
     */
    public function get(): Collection;

    /**
     * Check if the related api call was successful
     * @return bool
     */
    public function successful(): bool;
}
