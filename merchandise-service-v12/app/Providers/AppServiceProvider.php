<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\AcknowledgementRepository;
use App\Repositories\DispatchRepository;
use App\Repositories\Interfaces\AcknowledgementRepositoryInterface;
use App\Repositories\Interfaces\DispatchRepositoryInterface;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Interfaces\SkuRepositoryInterface;
use App\Repositories\InvoiceRepository;
use App\Repositories\OrderRepository;
use App\Repositories\SkuRepository;
use App\Services\Acknowledgement\AcknowledgementService;
use App\Services\Acknowledgement\AcknowledgementServiceInterface;
use App\Services\Approval\ApprovalService;
use App\Services\Approval\ApprovalServiceInterface;
use App\Services\Approval\ApprovalWorkflowService;
use App\Services\Approval\ApprovalWorkflowServiceInterface;
use App\Services\Dispatch\DispatchService;
use App\Services\Dispatch\DispatchServiceInterface;
use App\Services\Invoice\InvoiceService;
use App\Services\Invoice\InvoiceServiceInterface;
use App\Services\Order\OrderService;
use App\Services\Order\OrderServiceInterface;
use App\Services\Sku\SkuService;
use App\Services\Sku\SkuServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories
        $this->app->bind(SkuRepositoryInterface::class, SkuRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(DispatchRepositoryInterface::class, DispatchRepository::class);
        $this->app->bind(AcknowledgementRepositoryInterface::class, AcknowledgementRepository::class);
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);

        // Services
        $this->app->bind(SkuServiceInterface::class, SkuService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(ApprovalServiceInterface::class, ApprovalService::class);
        $this->app->bind(ApprovalWorkflowServiceInterface::class, ApprovalWorkflowService::class);
        $this->app->bind(DispatchServiceInterface::class, DispatchService::class);
        $this->app->bind(InvoiceServiceInterface::class, InvoiceService::class);

        // AcknowledgementService has a dep on InvoiceService — use closure to resolve
        $this->app->bind(AcknowledgementServiceInterface::class, function ($app) {
            return new AcknowledgementService(
                $app->make(AcknowledgementRepositoryInterface::class),
                $app->make(OrderRepositoryInterface::class),
                $app->make(InvoiceServiceInterface::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
