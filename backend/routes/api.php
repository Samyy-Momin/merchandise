<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\VendorOrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminCategoryController;

// All routes here will be served under the /api prefix by Laravel 11 routing config

Route::middleware(['keycloak.auth'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/buyer', function () {
        return response()->json(['message' => 'buyer ok']);
    })->middleware('role:buyer');

    Route::get('/approver', function () {
        return response()->json(['message' => 'approver ok']);
    })->middleware('role:approver');

    Route::get('/vendor', function () {
        return response()->json(['message' => 'vendor ok']);
    })->middleware('role:vendor');

    Route::get('/admin', function () {
        return response()->json(['message' => 'admin ok']);
    })->middleware('role:admin');

    // Admin management APIs
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/products', [AdminProductController::class, 'store']);
        Route::put('/admin/products/{id}', [AdminProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [AdminProductController::class, 'destroy']);
        Route::post('/admin/categories', [AdminCategoryController::class, 'store']);
    });

    // Products (accessible to any authenticated user; restrict later if needed)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    // Orders
    Route::middleware('role:buyer')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
    });
    Route::middleware('role:buyer,approver')->group(function () {
        Route::get('/orders', [OrderController::class, 'index']);
    });
    Route::middleware('role:buyer,approver,vendor')->group(function () {
        Route::get('/orders/{id}', [OrderController::class, 'show']);
    });
    Route::middleware('role:approver')->group(function () {
        Route::post('/orders/{id}/approve', [OrderController::class, 'approve']);
        Route::get('/approvals', [ApprovalController::class, 'index']);
        Route::get('/approvals/stats', [ApprovalController::class, 'stats']);
        Route::get('/approvals/{id}', [ApprovalController::class, 'show']);
    });

    // Vendor APIs
    Route::middleware('role:vendor')->group(function () {
        Route::get('/vendor/orders', [VendorOrderController::class, 'list']);
        Route::post('/vendor/orders/{id}/process', [VendorOrderController::class, 'process']);
        Route::post('/vendor/orders/{id}/dispatch', [VendorOrderController::class, 'dispatch']);
        Route::post('/vendor/orders/{id}/transit', [VendorOrderController::class, 'transit']);
        Route::post('/vendor/orders/{id}/deliver', [VendorOrderController::class, 'deliver']);
    });

    // Tracking for buyer/approver/vendor
    Route::middleware('role:buyer,approver,vendor')->group(function () {
        Route::get('/orders/{id}/tracking', [OrderController::class, 'tracking']);
        Route::get('/orders/{id}/issues', [OrderController::class, 'issues']);
        Route::get('/orders/{id}/full-details', [OrderController::class, 'fullDetails']);
        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/by-order/{orderId}', [InvoiceController::class, 'byOrder']);
        Route::get('/invoices/{orderId}/pdf', [InvoiceController::class, 'pdf']);
        Route::get('/invoices/{orderId}/excel', [InvoiceController::class, 'excel']);
    });

    // Addresses (buyer only)
    Route::middleware('role:buyer')->group(function () {
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{id}', [AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
        // Buyer finalization actions
        Route::post('/orders/{id}/acknowledge', [OrderController::class, 'acknowledge']);
        Route::post('/orders/{id}/issue', [OrderController::class, 'reportIssue']);
    });
});
