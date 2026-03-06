<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_report_documents', function (Blueprint $table): void {
            $table->foreignId('diagnostic_report_id')->constrained('diagnostic_reports')->cascadeOnDelete();
            $table->foreignUuid('uploaded_document_uuid')->constrained('uploaded_documents', 'uuid')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['diagnostic_report_id', 'uploaded_document_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_report_documents');
    }
};
