<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table): void {
            $table->string('value_type', 16)->default('numeric');
            $table->decimal('value_number', 15, 5)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->text('value_text')->nullable();
            $table->index('value_type');
        });

        DB::table('observations')->update([
            'value_type' => 'numeric',
            'value_number' => DB::raw('value'),
        ]);

        Schema::table('observations', function (Blueprint $table): void {
            $table->dropColumn('value');
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table): void {
            $table->decimal('value', 15, 5)->nullable();
        });

        DB::table('observations')->update([
            'value' => DB::raw(
                "CASE
                    WHEN value_type = 'numeric' THEN value_number
                    WHEN value_type = 'boolean' THEN CASE WHEN value_boolean = 1 THEN 1 ELSE 0 END
                    ELSE NULL
                END"
            ),
        ]);

        Schema::table('observations', function (Blueprint $table): void {
            $table->dropIndex(['value_type']);
            $table->dropColumn(['value_type', 'value_number', 'value_boolean', 'value_text']);
        });
    }
};
