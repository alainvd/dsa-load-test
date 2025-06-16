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
        Schema::table('api_errors', function (Blueprint $table) {
            $table->string('url')->nullable()->after('id');
            $table->string('method')->nullable()->after('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_errors', function (Blueprint $table) {
            $table->dropColumn(['url', 'method']);
        });
    }
};
