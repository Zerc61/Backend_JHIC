<?php

namespace App\Traits;

trait FilterByOwnership
{
    /**
     * Filter query berdasarkan manager_id milik user yang login.
     * Admin lihat semua, manager hanya miliknya.
     */
    protected function applyOwnershipFilter($query, string $column = 'manager_id'): mixed
    {
        if (auth()->user()->role === 'manager') {
            $query->where($column, auth()->id());
        }

        return $query;
    }

    /**
     * Filter query berdasarkan user_id milik UMKM owner yang login.
     */
    protected function applyUmkmFilter($query): mixed
    {
        if (auth()->user()->role === 'umkm') {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }
}