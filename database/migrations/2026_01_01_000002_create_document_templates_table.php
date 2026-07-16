<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type');
            $table->string('version');
            $table->string('template_format');
            $table->string('extension');
            $table->string('filename');
            $table->string('storage_path');
            $table->string('checksum_sha256')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['document_type', 'version', 'template_format', 'extension'], 'templates_unique_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
