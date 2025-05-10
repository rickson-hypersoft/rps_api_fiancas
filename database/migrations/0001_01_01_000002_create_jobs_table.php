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
        Schema::connection('firebird')->create('JOBS', function (Blueprint $table) {
            $table->integer('ID')->primary();
            $table->string('QUEUE')->index();
            $table->longText('PAYLOAD');
            $table->unsignedTinyInteger('ATTEMPTS');
            $table->unsignedInteger('RESERVED_AT')->nullable();
            $table->unsignedInteger('AVAILABLE_AT');
            $table->unsignedInteger('CREATED_AT');
        });

        Schema::connection('firebird')->create('JOB_BATCHES', function (Blueprint $table) {
            $table->integer('ID')->primary();
            $table->string('NAME');
            $table->integer('TOTAL_JOBS');
            $table->integer('PENDING_JOBS');
            $table->integer('FAILED_JOBS');
            $table->longText('FAILED_JOB_IDS');
            $table->mediumText('OPTIONS')->nullable();
            $table->integer('CANCELLED_AT')->nullable();
            $table->integer('CREATED_AT');
            $table->integer('FINISHED_AT')->nullable();
        });

        Schema::connection('firebird')->create('FAILED_JOBS', function (Blueprint $table) {
            $table->string('UUID')->primary();
            $table->text('CONNECTION');
            $table->text('QUEUE');
            $table->longText('PAYLOAD');
            $table->longText('EXCEPTION');
            $table->timestamp('FAILED_AT')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('firebird')->dropIfExists('JOBS');
        Schema::connection('firebird')->dropIfExists('JOB_BATCHES');
        Schema::connection('firebird')->dropIfExists('FAILED_JOBS');
    }
};
