<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('storage_disk', ['local', 's3']);
            $table->unsignedBigInteger('file_size_bytes');
            $table->enum('mime_type', ['application/pdf']);
            $table->char('file_hash_sha256', 64);
            $table->text('parsed_result')->nullable();
            $table->text('anonymised_result')->nullable();
            $table->jsonb('anonymised_artifacts')->nullable();
            $table->jsonb('normalized_result')->nullable();
            $table->json('final_result')->nullable();
            $table->jsonb('transliteration_mapping')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('uploaded_documents', function (Blueprint $table): void {
            $table->unique(['user_id', 'file_hash_sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_documents');
    }
};
