<?php

namespace App\Repositories\Contracts;

interface PositionRepositoryInterface
{
    public function updateOrCreate(array $conditions, array $attributes, int $expireHours = null);
}
