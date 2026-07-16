<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generation_events', function (Blueprint $table): void {
            $table->id();
            $table->string('document_id', 32);
            $table->string('event_type');
            $table->text('message')->nullable();
            $table->text('context_json')->nullable();
            $table->timestamp('created_at');

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_events');
    }
};
