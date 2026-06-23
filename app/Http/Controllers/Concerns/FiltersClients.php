<?php

namespace App\Http\Controllers\Concerns;

trait FiltersClients
{
    /** Apply a client search (name / email / GC id) to a User query builder. */
    protected function matchClient($u, string $search): void
    {
        $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");

        if (stripos($search, 'GC') === 0 && ($d = ltrim(preg_replace('/\D/', '', $search), '0')) !== '') {
            $u->orWhere('id', (int) $d);
        } elseif (ctype_digit($search)) {
            $u->orWhere('id', (int) $search);
        }
    }
}
