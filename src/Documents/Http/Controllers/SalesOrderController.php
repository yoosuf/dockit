<?php

namespace Yoosuf\Document\Documents\Http\Controllers;

use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Repositories\SalesOrderRepository;
use Yoosuf\Document\Documents\Support\ErrorCodes;

class SalesOrderController
{
    public function __construct(private readonly SalesOrderRepository $salesOrderRepository)
    {
    }

    public function show(int $salesOrderId)
    {
        $salesOrder = $this->salesOrderRepository->find($salesOrderId);

        if ($salesOrder === null) {
            throw new DocumentException(
                ErrorCodes::SALES_ORDER_NOT_FOUND,
                'Sales order was not found.',
                404,
            );
        }

        return response()->json([
            'sales_order_id' => $salesOrder->sales_order_id,
            'customer_name' => $salesOrder->customer_name,
            'invoice_number' => $salesOrder->invoice_number,
            'currency' => $salesOrder->currency,
            'total_amount' => $salesOrder->total_amount,
            'issued_at' => $salesOrder->issued_at,
        ]);
    }
}
