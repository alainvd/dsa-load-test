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
        Schema::create('api_errors', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('method');
            $table->text('request_payload')->nullable();
            $table->integer('statement_response_id')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_errors');
    }
};
