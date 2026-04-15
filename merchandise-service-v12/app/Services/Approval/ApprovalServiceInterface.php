<?php

declare(strict_types=1);

namespace App\Services\Approval;

use App\DTOs\ApprovalDTO;
use App\Models\MerchandiseOrder;

interface ApprovalServiceInterface
{
    public function approve(int $orderId, ApprovalDTO $dto): MerchandiseOrder;

    public function reject(int $orderId, int $staffId, string $reason): MerchandiseOrder;
}
