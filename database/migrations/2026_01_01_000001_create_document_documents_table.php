<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->string('document_id', 32)->unique();
            $table->string('document_type');
            $table->string('version');
            $table->string('template_format');
            $table->string('output_format');
            $table->string('filename');
            $table->string('storage_path');
            $table->unsignedBigInteger('size_bytes');
            $table->string('source_mode');
            $table->text('request_payload_json')->nullable();
            $table->string('status')->default('completed');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['document_type', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
