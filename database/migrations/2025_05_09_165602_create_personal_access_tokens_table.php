<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('firebird')->create('PERSONAL_ACCESS_TOKENS', function (Blueprint $table) {
            $table->integer('ID')->primary();
            $table->morphs('TOKENABLE');
            $table->string('NAME');
            $table->string('TOKEN', 2000)->unique();
            $table->text('ABILITIES')->nullable();
            $table->timestamp('LAST_USED_AT')->nullable();
            $table->timestamp('EXPIRES_AT')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PERSONAL_ACCESS_TOKENS');
    }
};
