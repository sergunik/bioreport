<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mongodb')->create('users', function (Blueprint $table) {
            $table->unique('email');
        });

        Schema::connection('mongodb')->create('password_reset_tokens', function (Blueprint $table) {
            $table->index('email');
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('users');
        Schema::connection('mongodb')->dropIfExists('password_reset_tokens');
    }
};
