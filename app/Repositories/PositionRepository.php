<?php

namespace App\Repositories;

use App\Models\Positions;
use App\Repositories\Contracts\PositionRepositoryInterface;
use Carbon\Carbon;

class PositionRepository implements PositionRepositoryInterface
{
    public function updateOrCreate(array $conditions, array $attributes, int $expireHours = null)
    {
        $position = Positions::firstOrNew($conditions);

        $shouldUpdate = !$position->exists ||
            ($expireHours && $position->updated_at->diffInHours() > $expireHours);

        if ($shouldUpdate) {
            $position->fill($attributes)->save();
        }

        return $position;
    }
}
