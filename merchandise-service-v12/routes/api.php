<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\AcknowledgementController;
use App\Http\Controllers\Api\V2\ApprovalWorkflowController;
use App\Http\Controllers\Api\V2\DispatchController;
use App\Http\Controllers\Api\V2\HealthController;
use App\Http\Controllers\Api\V2\InternalTenantLifecycleController;
use App\Http\Controllers\Api\V2\InvoiceController;
use App\Http\Controllers\Api\V2\OrderApprovalController;
use App\Http\Controllers\Api\V2\OrderController;
use App\Http\Controllers\Api\V2\SkuController;
use Illuminate\Support\Facades\Route;

Route::prefix('v2/merchandise')->middleware(['keycloak.jwt', 'tenant'])->group(function () {

    // Health
    Route::get('health', [HealthController::class, 'check']);

    // SKU Catalogue
    Route::get('skus', [SkuController::class, 'index']);
    Route::get('skus/{id}', [SkuController::class, 'show']);
    Route::post('skus', [SkuController::class, 'store'])->middleware('role:super_admin,vendor');
    Route::put('skus/{id}', [SkuController::class, 'update'])->middleware('role:super_admin,vendor');
    Route::delete('skus/{id}', [SkuController::class, 'destroy'])->middleware('role:super_admin,vendor');

    // Orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('orders', [OrderController::class, 'store'])->middleware('role:customer');
    Route::delete('orders/{id}', [OrderController::class, 'cancel'])->middleware('role:customer');

    // Approval
    Route::post('orders/{id}/approve', [OrderApprovalController::class, 'approve'])
        ->middleware('role:admin,senior_manager,super_admin');
    Route::post('orders/{id}/reject', [OrderApprovalController::class, 'reject'])
        ->middleware('role:admin,senior_manager,super_admin');
    Route::post('approvals', [ApprovalWorkflowController::class, 'store'])
        ->middleware('role:customer,admin,senior_manager,super_admin');
    Route::post('approvals/{id}/submit', [ApprovalWorkflowController::class, 'submit'])
        ->middleware('role:customer,admin,senior_manager,super_admin');
    Route::post('approvals/{id}/approve', [ApprovalWorkflowController::class, 'approve'])
        ->middleware('role:admin,senior_manager,super_admin');
    Route::post('approvals/{id}/reject', [ApprovalWorkflowController::class, 'reject'])
        ->middleware('role:admin,senior_manager,super_admin');

    // Dispatch
    Route::get('dispatches', [DispatchController::class, 'index'])
        ->middleware('role:super_admin,vendor');
    Route::post('orders/{id}/dispatch', [DispatchController::class, 'dispatch'])
        ->middleware('role:super_admin,vendor');

    // Acknowledgement
    Route::post('orders/{id}/acknowledge', [AcknowledgementController::class, 'acknowledge'])
        ->middleware('role:customer');
    Route::post('acknowledgements/{id}/approve', [AcknowledgementController::class, 'approve'])
        ->middleware('role:super_admin,vendor');
    Route::post('acknowledgements/{id}/reject', [AcknowledgementController::class, 'reject'])
        ->middleware('role:super_admin,vendor');

    // Invoices
    Route::get('invoices', [InvoiceController::class, 'index'])
        ->middleware('role:super_admin,vendor');
    Route::get('invoices/{id}', [InvoiceController::class, 'show'])
        ->middleware('role:super_admin,vendor,customer');
    Route::post('orders/{id}/invoice', [InvoiceController::class, 'create'])
        ->middleware('role:super_admin,vendor');
    Route::post('invoices/{id}/payments', [InvoiceController::class, 'recordPayment'])
        ->middleware('role:super_admin,vendor');
    Route::get('invoices/{id}/download', [InvoiceController::class, 'download'])
        ->middleware('role:customer,super_admin,vendor');
});

Route::post('internal/tenants/{companyId}/bootstrap', [InternalTenantLifecycleController::class, 'bootstrap'])->whereNumber('companyId');
Route::get('internal/tenants/{companyId}/readiness', [InternalTenantLifecycleController::class, 'readiness'])->whereNumber('companyId');
