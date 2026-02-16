<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('uploaded_document_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::table('pdf_jobs', function (Blueprint $table): void {
            $table->index('status');
            $table->index('locked_at');
            $table->index('uploaded_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_jobs');
    }
};
