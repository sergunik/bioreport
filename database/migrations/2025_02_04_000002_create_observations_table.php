<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_report_id')->constrained()->cascadeOnDelete();
            $table->string('biomarker_name');
            $table->string('biomarker_code')->nullable();
            $table->decimal('value', 15, 5);
            $table->string('unit', 64);
            $table->decimal('reference_range_min', 15, 5)->nullable();
            $table->decimal('reference_range_max', 15, 5)->nullable();
            $table->string('reference_unit', 64)->nullable();
            $table->timestamps();
        });

        Schema::table('observations', function (Blueprint $table): void {
            $table->index('biomarker_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
