<?php

use Illuminate\Support\Facades\Route;
use Yoosuf\Document\Documents\Http\Controllers\DocumentController;
use Yoosuf\Document\Documents\Http\Controllers\DocumentTypeController;
use Yoosuf\Document\Documents\Http\Controllers\SalesOrderController;

Route::prefix(config('document.route_prefix', 'api/v1'))->middleware('api')->group(function (): void {
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document_id}', [DocumentController::class, 'show'])->where('document_id', '^[0-9a-f]{32}$');
    Route::get('/documents/{document_id}/content', [DocumentController::class, 'content'])->where('document_id', '^[0-9a-f]{32}$');
    Route::get('/documents/{document_id}/events', [DocumentController::class, 'events'])->where('document_id', '^[0-9a-f]{32}$');
    Route::post('/documents/{document_id}/requeue', [DocumentController::class, 'requeue'])->where('document_id', '^[0-9a-f]{32}$');
    Route::delete('/documents/{document_id}', [DocumentController::class, 'destroy'])->where('document_id', '^[0-9a-f]{32}$');

    Route::get('/document-types', [DocumentTypeController::class, 'index']);
    Route::get('/document-types/{document_type}/inspect', [DocumentTypeController::class, 'inspect']);

    Route::get('/sales-orders/{sales_order_id}', [SalesOrderController::class, 'show'])->whereNumber('sales_order_id');
});
