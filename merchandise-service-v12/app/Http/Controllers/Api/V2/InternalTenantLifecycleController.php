<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InternalTenantLifecycleController extends Controller
{
    public function bootstrap(Request $request, int $companyId): JsonResponse
    {
        $unitId = (int) $request->input('unit_id', 1);
        $timestamp = now()->toIso8601String();

        Cache::put($this->bootstrapCacheKey($companyId, $unitId), [
            'requested_at' => $timestamp,
            'business_type' => $request->input('business_type'),
        ], now()->addHour());

        return response()->json([
            'status' => 'accepted',
            'data' => [
                'company_id' => $companyId,
                'unit_id' => $unitId,
                'service' => 'approver-service-v12',
                'bootstrap_requested_at' => $timestamp,
            ],
        ], 202);
    }

    public function readiness(Request $request, int $companyId): JsonResponse
    {
        $unitId = (int) $request->input('unit_id', 1);
        $databaseHealthy = $this->databaseHealthy();
        $approvalRequestsTable = Schema::hasTable('approval_requests');
        $approvalActionsTable = Schema::hasTable('approval_actions');
        $ordersTable = Schema::hasTable('merchandise_orders');
        $schemaReady = $approvalRequestsTable && $approvalActionsTable && $ordersTable;
        $bootstrapRequestedAt = data_get(Cache::get($this->bootstrapCacheKey($companyId, $unitId), []), 'requested_at');
        $bootstrapped = $databaseHealthy && $schemaReady && is_string($bootstrapRequestedAt) && $bootstrapRequestedAt !== '';
        $status = $bootstrapped ? 'ready' : 'pending';

        return response()->json([
            'status' => 'success',
            'data' => [
                'service' => 'approver-service-v12',
                'company_id' => $companyId,
                'unit_id' => $unitId,
                'status' => $status,
                'bootstrapped' => $bootstrapped,
                'healthy' => $databaseHealthy && $schemaReady,
                'checks' => [
                    'database' => $databaseHealthy ? 'ok' : 'error',
                    'approval_requests_table' => $approvalRequestsTable,
                    'approval_actions_table' => $approvalActionsTable,
                    'merchandise_orders_table' => $ordersTable,
                ],
                'bootstrap_requested_at' => $bootstrapRequestedAt,
            ],
        ]);
    }

    private function databaseHealthy(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function bootstrapCacheKey(int $companyId, int $unitId): string
    {
        return "internal:tenant-bootstrap:approver:{$companyId}:{$unitId}";
    }
}
