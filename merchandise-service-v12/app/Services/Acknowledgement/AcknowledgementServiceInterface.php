<?php

declare(strict_types=1);

namespace App\Services\Acknowledgement;

use App\Models\MerchandiseAcknowledgement;

interface AcknowledgementServiceInterface
{
    public function acknowledge(int $orderId, int $customerId, ?string $notes): MerchandiseAcknowledgement;

    public function approveAcknowledgement(int $ackId, int $staffId): MerchandiseAcknowledgement;

    public function rejectAcknowledgement(int $ackId, int $staffId, string $reason): MerchandiseAcknowledgement;
}
