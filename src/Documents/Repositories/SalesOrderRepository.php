<?php

namespace Yoosuf\Document\Documents\Repositories;

use Yoosuf\Document\Documents\Models\SalesOrder;

class SalesOrderRepository
{
    public function find(int $salesOrderId): ?SalesOrder
    {
        return SalesOrder::query()->where('sales_order_id', $salesOrderId)->first();
    }
}
