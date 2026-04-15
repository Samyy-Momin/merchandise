<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\MerchandiseDispatch;
use App\Repositories\Interfaces\DispatchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DispatchRepository implements DispatchRepositoryInterface
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = MerchandiseDispatch::with('order');

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        return $query->orderBy('id', 'desc')->paginate(20);
    }

    public function create(array $data): MerchandiseDispatch
    {
        return MerchandiseDispatch::create($data);
    }
}
