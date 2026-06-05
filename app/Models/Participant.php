<?php

namespace App\Models;

use App\Enums\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['guest_id', 'team', 'last_voted_at'])]
class Participant extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team' => Team::class,
            'last_voted_at' => 'datetime',
        ];
    }
}