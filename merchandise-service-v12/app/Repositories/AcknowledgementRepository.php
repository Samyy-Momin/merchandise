<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\AcknowledgementStatus;
use App\Exceptions\AcknowledgementNotFoundException;
use App\Models\MerchandiseAcknowledgement;
use App\Repositories\Interfaces\AcknowledgementRepositoryInterface;

class AcknowledgementRepository implements AcknowledgementRepositoryInterface
{
    public function create(array $data): MerchandiseAcknowledgement
    {
        return MerchandiseAcknowledgement::create($data);
    }

    public function findOrFail(int $id): MerchandiseAcknowledgement
    {
        $ack = MerchandiseAcknowledgement::with('order')->find($id);

        if ($ack === null) {
            throw new AcknowledgementNotFoundException("Acknowledgement #{$id} not found.");
        }

        return $ack;
    }

    public function approve(MerchandiseAcknowledgement $ack, int $staffId): MerchandiseAcknowledgement
    {
        $ack->update([
            'status' => AcknowledgementStatus::Approved,
            'reviewed_by' => $staffId,
            'reviewed_at' => now(),
        ]);

        return $ack->fresh();
    }

    public function reject(MerchandiseAcknowledgement $ack, int $staffId, string $reason): MerchandiseAcknowledgement
    {
        $ack->update([
            'status' => AcknowledgementStatus::Rejected,
            'reviewed_by' => $staffId,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $ack->fresh();
    }
}
