<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('firebird')->table('PROPOSTAS', function (Blueprint $table) {
            $table->string('LINK_HASH')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->table('PROPOSTAS', function (Blueprint $table) {
            $table->dropColumn('LINK_HASH');
        });
    }
};
