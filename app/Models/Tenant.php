<?php

namespace App\Models;

use Spatie\Multitenancy\Models\Tenant as ModelsTenant;

class Tenant extends ModelsTenant
{
    public function scopeWhereUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }
}