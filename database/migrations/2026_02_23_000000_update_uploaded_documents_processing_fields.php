<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploaded_documents', function (Blueprint $table): void {
            $table->dropColumn(['ml_raw_result', 'ml_normalized_result']);
            $table->text('parsed_result')->nullable();
            $table->text('anonymised_result')->nullable();
            $table->jsonb('anonymised_artifacts')->nullable();
            $table->jsonb('normalized_result')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_documents', function (Blueprint $table): void {
            $table->dropColumn([
                'parsed_result',
                'anonymised_result',
                'anonymised_artifacts',
                'normalized_result',
            ]);
            $table->jsonb('ml_raw_result')->nullable();
            $table->jsonb('ml_normalized_result')->nullable();
        });
    }
};
