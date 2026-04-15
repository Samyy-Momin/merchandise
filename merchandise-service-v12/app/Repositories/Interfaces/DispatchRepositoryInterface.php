<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\MerchandiseDispatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DispatchRepositoryInterface
{
    public function list(array $filters): LengthAwarePaginator;

    public function create(array $data): MerchandiseDispatch;
}
