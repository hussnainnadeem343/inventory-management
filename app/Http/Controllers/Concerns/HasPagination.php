<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait HasPagination
{
    /**
     * Paginate query with dynamic per_page selection defaulting to 10.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $default
     * @param  int[]  $allowed
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginateWithPerPage(
        Builder $query,
        Request $request,
        int $default = 10,
        array $allowed = [10, 20, 50, 100, 500]
    ): LengthAwarePaginator {
        $perPage = in_array((int) $request->query('per_page', $default), $allowed, true)
            ? (int) $request->query('per_page', $default)
            : $default;

        return $query->paginate($perPage)->withQueryString();
    }
}
