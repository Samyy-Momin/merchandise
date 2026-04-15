<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\MerchandiseAcknowledgement;

interface AcknowledgementRepositoryInterface
{
    public function create(array $data): MerchandiseAcknowledgement;

    public function findOrFail(int $id): MerchandiseAcknowledgement;

    public function approve(MerchandiseAcknowledgement $ack, int $staffId): MerchandiseAcknowledgement;

    public function reject(MerchandiseAcknowledgement $ack, int $staffId, string $reason): MerchandiseAcknowledgement;
}
